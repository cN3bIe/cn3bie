<?php
/*
require_once(COMPONENTS.'/appclear/containers/FF.php');
 */
/*
 * FFv1.042 23.01.2017  FormField
		Способ применения:
		$fields = array(
			'name'=>array('type'=>'text','empty'=>false,'insertdb'=>true,'rename'=>'user_*'),
			'phone'=>array('type'=>'regex','empty'=>false,'insertdb'=>true,'rename'=>'user_*','regex'=>'/^\+?\s?\d\s?\(?\d{3}\)?\s?\d{3}(-|\s)?\d{2}(-|\s)?\d{2}$/'),
			'email'=>array('type'=>'regex','empty'=>false,'insertdb'=>true,'rename'=>'user_*', 'regex'=>'/.+@.+\..+/', 'busy'=>true),
			'about'=>array('type'=>'text','empty'=>true,'insertdb'=>true,'rename'=>'user_*'),
			'inclient'=>array('type'=>'bool','empty'=>true,'insertdb'=>true),
			'specializations'=>array('type'=>'array','empty'=>true,'insertdb'=>true,'db'=>array('method'=>'GetDocumentById','journal'=>'ref'),'rename'=>'specialization')
		);
		$this->update_fields = array();
		if($this->ff = FF::Install($fields)->GetFields($_POST)->CheckFields()){
			if($this->ff->UpdateDB(array('journal'=>'masters','path'=>'/'.$this->account['alias']))){
			}
		}
*/
class FF{
	static protected $error = null;
	protected $DB = null;
	protected $fields = null;
	public $object = null;
	public $docuemnt = null;
	public $documents = null;
	public $children = null;
	static protected $FIELDS = null;
	static protected $OBJECTS = null;
	static protected $DOCUMENT = null;
	static protected $DOCUMENTS = null;
	static protected $CHILDREN = null;
	protected function __construct($fields){
		// $this->DB = &OpenDM('content', 'Common.Document'); // Подключаем местну. ORM
		$this->fields = $fields;
		if(!is_null($this->fields)) self::$FIELDS[]=&$this->fields;
	}
	// Создание элементария
	static public function Install($fields){
		return new self($fields);
	}
	/*Метод rename подставляет измененное имя вместо изначально указанного
		'name'=>array(....,'rename'=>'user_*'),
		name - изначальное и в основном указывается как это поле записано в БД
		rename - указывается если в блоке form ему дали другое имя
		* - будет заменено name
	*/
	protected function Name($name){
		if(!isset($this->fields[$name]['rename'])) return $name;
		return str_replace('*',$name,$this->fields[$name]['rename']);
	}
	// Очистка поля от мешуры
	// FFv1.04 добавлена возможность очистки по regex
	static public function CV($str, $regex=''){
		if(empty($regex)) return trim(strip_tags($str));
		if($str=FF::CV($str)) $str=preg_match($regex,$str,$arr_str)?array_shift($arr_str):'';
		return $str;
	}
	// Выводит содержимое obj методом var_dump(), если указан title то выведет его вначале
	static public function dump($obj, $title=''){
		echo '<hr><xmp>';
		if($title)echo $title;
		var_dump($obj);
		echo '</xmp><hr>';
	}
	//Возвращает объект из self::DOCUMENT
	static public function GetD($name=''){
		$doc=self::$DOCUMENT;
		return ($name?$doc[$name]:$doc);
	}
	//Возвращает объект из self::DOCUMENTS
	static public function GetDs($name=''){
		$docs=self::$DOCUMENTS;
		return ($name?$docs[$name]:$docs);
	}
	// Default settings for fields
	public function SetFields(){
		foreach($this->fields as $name=>$params){
			switch($params['type']){
				case 'enum':
					$this->object[$name] = $params['enum'][0];
					break;
				case 'bool':
					$this->object[$name] = 'true';
					break;
				default:
					$this->object[$name] = '';
			}
		}
	}
	public function GetField( $name ){
		return $this->fields[$name];
	}
	/*
		Получение полей формы из ассцоиативного массива
	*/
	public function GetFields($data){
		foreach($this->fields as $name=>$params){
			switch($params['type']){
				case 'regex':
				case 'text':
				case 'pass':
					$this->object[$name] = self::CV($data[$this->Name($name)]);
					break;
				case 'int':
					$this->object[$name] =(int)(self::CV($data[$this->Name($name)]));
					break;
				case 'enum':
					foreach($params['enum'] as $key=>$value){
						if(self::CV($data[$this->Name($name)]) === $value)$this->object[$name] = $value;
					}
					break;
				case 'bool':
					$this->object[$name] = isset($data[$this->Name($name)]);
					break;
				case 'array':
					if(is_array($data[$this->Name($name)])){
						$clear_arr = array();
						foreach($data[$this->Name($name)] as $key=>$value){
							$clear_arr[]=self::CV($value);
						}
						$this->object[$name] = implode(';',$clear_arr);
					}else $this->object[$name] = '';/* self::AppendError($name.'.empty'); */
					break;
			}
		}
		if(!is_null($this->object))self::$OBJECTS[]=&$this->object;
		if(is_null($this->object)) self::AppendError('object.empty');
		return $this;
	}
	/*
		Метод проверки  полей
	*/
	public function CheckFields(){
		$DB = false;
		//Проверка на заполненность, если "empty=false"
		if(self::IsNotError()){
			foreach($this->fields as $name=>$params){
				if(!$params['empty']){
					if(!$this->object[$name]) self::AppendError($this->Name($name));
				}
				// Если есть такой параметр, то проверять на существование в БД.
				if(isset($params['db']))$DB = true;
			}
		}
		// Проверка корректности
		if(self::IsNotError()){
			foreach($this->fields as $name=>$params){
				if(self::IsNotError()){
					switch($params['type']){
						case 'int':
							if(isset($params['interval'])){
								list($min,$max) = explode('-',$params['interval']);
								if((isset($min) && $this->object[$name] < $min) ||(isset($max) && $this->object[$name] > $max)) self::AppendError($this->Name($name).'.interval');
							}
							break;
						case 'array':
						case 'regex':
							if(isset($params['regex']))if(!preg_match($params['regex'], $this->object[$name])) self::AppendError($this->Name($name));
							break;
						case 'enum':
							if(!$params['empty']){
								$enum = true;
								foreach($params['enum'] as $key=>$value){
									if($this->object[$name] === $value)$enum = false;
								}
								if($enum) self::AppendError($this->Name($name),$this->object[$name]);
							}
							break;
						default:break;
					}
				}
			}
		}
		// Проверка существования совпадений, занятости в БД
		/*
			'db'=>array('method'=>'GetDocumentById','journal'='string')
		*/
		if(self::IsNotError() && $DB){
			foreach($this->fields as $name=>$params){
				if(isset($params['db'])){
					switch($params['db']['method']){
						//Нужно указать дополнительный параметр journal
						case 'GetDocumentById':
							switch($params['type']){
								case 'int':
									$this->document = $this->DB->GetDocumentById($params['db']['journal'],(int)$this->object[$name]);
									if(stripos($this->document['journal_alias'],$params['db']['journal'])===false) self::AppendError($this->Name($name).'.not.exist');
									else self::$DOCUMENT[$name]=$this->document;
									break;
								case 'array':
									foreach(explode(';',$this->object[$name]) as $key=>$id){
										$this->documents[(int)$id] = $this->DB->GetDocumentById($params['db']['journal'],(int)$id);
										if($this->documents[(int)$id]['journal_alias'] !== $params['db']['journal']) self::AppendError($this->Name($name).'.'.$id.'.not.exist');
									}
									if(!is_null($this->documents)) self::$DOCUMENTS[]=$this->documents;
									break;
							}
							break;
						case 'GetChildrenById':
							if(!$this->document = $this->DB->GetDocumentById($params['db']['journal'],(int)$this->object[$name])) self::AppendError($name.'.document.not.exist');
							else{
								if($this->document['journal_alias'] !== $params['db']['journal']) self::AppendError($name.'.not.exist');
								else self::$DOCUMENT[]=&$this->document;
								$template=is_array($params['db']['template'])?$params['db']['template']:array($this->document['journal_alias'].'.'.$params['db']['template']);
								$this->DB->Clear();
								if(!$this->children = $this->DB->Select($this->document['journal_alias'],'/'.$this->document['alias'],$template)->FStorage) self::AppendError($this->Name($name).'.not.children');
								else self::$CHILDREN[]=&$this->children;
							}
							break;
						case 'Busy':
							$this->DB->Clear();
							$this->DB->SetSearchCondition();
							$RRootRelation = &$this->DB->SetSearchRelation($this->DB->SearchCondition, 'AND');
							if(isset($params['db']['id']))$this->DB->SetSearchTerm($RRootRelation, 'd.document_id', $params['db']['id'],'<>');
							$this->DB->SetSearchTerm($RRootRelation, $params['db']['template'].'.'.$name, $this->object[$name],'LIKE');
							$result = $this->DB->Select($params['db']['journal'],'*',array($params['db']['journal'].$params['db']['template']), '', array('count'=>true));
							if((int) $result) self::AppendError($this->Name($name).'.busy');
					}
				}
			}
		}
		return $this;
	}
	// Статический метод для добавления ошибок (с версии FFv1.02 поле $error является статичным)
	static public function AppendError($str, $value = true){
		self::$error[$str] = $value;
		return is_bool($value)?false:$value;
	}
	// Оставлен для совместимости. Аналог AppendError
	public function AddError($str, $value = true){
		self::$error[$str] = $value;
	}
	// Возвращает массив ошибок
	static public function GetError(){
		return self::$error;
	}
	// Cтатический метод для проверки наличия ошибок (с версии FFv1.02 поле $error является статичным)
	static public function IsNotError($success = true, $fail = false){
		return is_null(self::$error)?$success:$fail;
	}
	// Оставлен для совместимости. Аналог IsNotError
	public function HasError($success = true, $fail = false){
		return is_null(self::$error)?$success:$fail;
	}
	// Вывод всех доступных данных класса
	static public function BrowseError($debug = false){
		if(!$debug)if(self::IsNotError())return true;
		self::dump(self::$error,'error :');
		if($debug){
			session_start();
			foreach(array(
				'FIELDS: '=>self::$FIELDS,
				'OBJECTS: '=>self::$OBJECTS,
				'DOCUMENT: '=>self::$DOCUMENT,
				'DOCUMENTS: '=>self::$DOCUMENTS,
				'CHILDREN: '=>self::$CHILDREN,
				'$_GET: '=>$_GET,
				'$_POST: '=>$_POST,
				'$_SESSION: '=>$_SESSION
				) as $title=>$obj){
				self::dump($obj,$title);
			}
		}
		if($debug !== 'debug')die();
		return true;
	}
	// Возвращает ошибки в переданный DOM-объект
	static public function ReturnErrorInDOM($MainCN){
		if(!is_a($MainCN,'RmlNode')){
			self::AppendError('ReturnErrorInDOM.conteiner.isnot.RmlNode');
			return false;
		}
		if(self::IsNotError()) return false;
		$ErrorCN = &$MainCN->AddNode('Error');
		foreach(self::$error as $name=>$t){
			$ErrorCN->SetAttribute($name,'true');
		}
		return true;
	}
	// Возвращает поля формы в переданный RmlNode-объект.
	public function ReturnAllValueField($MainCN){
		if(!is_a($MainCN,'RmlNode')){
			self::AppendError('ReturnAllValueField.conteiner.isnot.RmlNode');
			return false;
		}
		$FieldCN = &$MainCN->AddNode('Field');
		foreach($this->object as $name=>$value){
			$FieldCN->SetAttribute($this->Name($name),$value);
		}
		return true;
	}
	/*
	Вставляет документы в БД.
	Параметры:
		'journal'=>'string',
		'template'=>'string',
		'path'=>'string',
		['additional_fields'=>'array']
	*/
	public function InsertDB($info){
		if(!$info) return self::AppendError('insert.not.params');
		$document_params['alias.editable'] = 'false';
		$document_field = array();
		foreach($this->fields as $name=>$params){
			if($params['insertdb']){
				switch($params['type']){
					case 'bool':
						$document_field[$name] = $this->object[$name]?'true':'false';
						break;
					default:
						$document_field[$name] = $this->object[$name];
						break;
				}
			}
		}
		if(isset($info['additional_fields'])){
			foreach($info['additional_fields'] as $name=>$value){
				$document_field[$name] = $value;
			}
		}
		if(!count($document_field)) return self::AppendError('insertdb.fields.document.empty');
		if(!$result=$this->DB->Insert($info['journal'].'.'.$info['template'],$info['path'],$document_field,$document_params)) self::AppendError('insert.db');
		return self::IsNotError((int)$result);
	}
	/*
	Обновляет/заменяет поля документа в БД
	Параметры:
		'journal'=>'string',
		'path'=>'/string',
		'additional_fields'=>'array(name_db=>value,...)'
	*/
	public function UpdateDB($info){
		if(!$info) return self::AppendError('update.not.params');
		$document_field = array();
		foreach($this->fields as $name=>$params){
			if($params['insertdb']){
				switch($params['type']){
					case 'bool':
						$document_field[$name] = $this->object[$name]?'true':'false';
						break;
					default:
						$document_field[$name] = $this->object[$name];
						break;
				}
			}
		}
		if(isset($info['additional_fields'])){
			foreach($info['additional_fields'] as $name=>$value){
				$document_field[$name] = $value;
			}
		}
		if(isset($info['debug'])) self::dump(array(
			'info: '=>$info,
			'document_field:'=>$document_field
		),'UpdateDB: ');
		if(!$result=$this->DB->Update($info['journal'],$info['path'],$document_field)) self::AppendError('update.db');
		return self::IsNotError((int)$result);
	}
	/*
	Стат метод изменения полей документа в БД
	Параметры:
		$fields=array(
			name_db=>value,
			...
		),
		$params=array(
			'journal'=>'string',
			'path'=>'/string'
		)
	*/
	static public function UpFields($fields,$params){
		if(!is_array($fields) || !count($fields)) return self::AppendError('.fields.empty');
		if(!is_array($params) || !count($params)) return self::AppendError('.params.empty');
		if(!$result=OpenDM('content', 'Common.Document')->Update($params['journal'],$params['path'],$fields)) return self::AppendError('result');
		return self::IsNotError($result);
	}
	/*
	Отправка почты пользователской части
	Шаблон сообщения, Тема сообщения, кому(Майл, Имя), поля для шаблона
	*/
	public function SendUserMail($MsgSubj,$recipient,$files = [] ,$DATA_files = null){
		if( !self::IsNotError() ) return $this;
		$body = ''.
			"<!doctype html>"
				."<html>"
					."<head>"
						."<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>"
						."<style>div,p,span,strong,b,em,i,a,li,td{-webkit-text-size-adjust:none;}td{vertical-align:middle}</style>"
						."<title>"
							.$MsgSubj
						."</title>"
					."</head>"
					."<body>"
						.'<h3>'.$MsgSubj.' с сайта <a href="'.$_SERVER['HTTP_HOST'].'">'.$_SERVER['HTTP_HOST'].'</a></h3>';
		if( is_array( $files ) && count( $files ) ):
			foreach( $files as $key=>$item ):
				$body .= ($item[0]?'<p>'.$item[0].': '.$item[1].'</p>':'');
			endforeach;
		endif;
		$body .= '<p>Дата: '.date('d.m.Y в H:i').'</p></body></html>';
		if( !is_null($DATA_files) && count( $DATA_files ) ){
			$mail = Mail::sendAttach($DATA_files['path'],$DATA_files['name'],$recipient,$MsgSubj,$body,$from);
		}else $mail = Mail::send($recipient,$MsgSubj,$body,$from);
		return $this;
	}
	public function SendAdminMail($MsgSubj,$recipient,$DATA_files = null){
		if( !self::IsNotError() ) return $this;
		$body = ''.
			"<!doctype html>"
				."<html>"
					."<head>"
						."<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>"
						."<style>div,p,span,strong,b,em,i,a,li,td{-webkit-text-size-adjust:none;}td{vertical-align:middle}</style>"
						."<title>"
							.$MsgSubj
						."</title>"
					."</head>"
					."<body>"
						.'<h3>'.$MsgSubj.' с сайта <a href="'.$_SERVER['HTTP_HOST'].'">'.$_SERVER['HTTP_HOST'].'</a></h3>';
		if( is_array( $this->object ) && count( $this->object ) ):
			foreach( $this->object as $key=>$item ):
				$body .= ($item?'<p>'.$this->GetField($key)['slug'].': '.$item.'</p>':'');
			endforeach;
		endif;
		$body .= '<p>Дата заявки: '.date('d.m.Y в H:i').'</p></body></html>';
		if( !is_null($DATA_files) && count($DATA_files)){
			$new_files = [];
			$uploaddir = cn3bie::get_cn3bie_dir().'uploads/';
			if( ! is_dir( $uploaddir ) ) mkdir( $uploaddir, 0777 );
			$file=array_shift($DATA_files);
			$file_type=explode('/',$file[type]);
			$new_path = $uploaddir . uniqid('img_').'.'.$file_type[1];
			if( move_uploaded_file( $file['tmp_name'], $new_path ) ){
				$new_files = realpath( $new_path );
				$mail = Mail::sendAttach($new_files,$file['name'],$recipient,$MsgSubj,$body,$from);
			}else FF::AppendError('file');
		}else $mail = Mail::send($recipient,$MsgSubj,$body,$from);
		return $this;
	}
	public function response($success = 'OK', $error = 'ERROR'){
		if( self::IsNotError() ) die(json_encode( $success ));
		else{
			// echo $error;
			// echo '<!--';
			// self::BrowseError();
			// self::info();
			// echo '-->';
			die(json_encode( self::GetError() ));
		}
	}
	/*
	Отправка почты админской части
	Доступ, Шаблон сообщения, Тема сообщения, поля для шаблона
	*/
	public function SendLeadBitrix( $title ){
		define('CRM_HOST', 'ilrusstroy.bitrix24.ru'); // your CRM domain name
		define('CRM_PORT', '443'); // CRM server port
		define('CRM_PATH', '/crm/configs/import/lead.php'); // CRM server REST service path

		// CRM server authorization data
		define('CRM_LOGIN', 'ilrusstroy-bot@bitrix24.ru'); // login of a CRM user able to manage leads
		define('CRM_PASSWORD', 'qwerty'); // password of a CRM user
		// OR you can send special authorization hash which is sent by server after first successful connection with login and password
		//define('CRM_AUTH', 'e54ec19f0c5f092ea11145b80f465e1a'); // authorization hash

		/********************************************************************************************/

		// POST processing
		if ($_SERVER['REQUEST_METHOD'] == 'POST'){
			// $leadData = $_POST['DATA'];
			// get lead data from the form
			$postData = [
				'TITLE' => $title,
				'SOURCE_ID' => 'WEB',
			];
			if( is_array( $this->object ) && count( $this->object ) ):
				foreach( $this->object as $key=>$item ):
					if( isset($this->fields[$key]['bitrix']) ) $postData[$this->fields[$key]['bitrix']] = $item;
				endforeach;
			endif;

			// append authorization data
			if ( defined('CRM_AUTH') ) $postData['AUTH'] = CRM_AUTH;
			else {
				$postData['LOGIN'] = CRM_LOGIN;
				$postData['PASSWORD'] = CRM_PASSWORD;
			}

			// open socket to CRM
			$fp = fsockopen("ssl://".CRM_HOST, CRM_PORT, $errno, $errstr, 30);
			if ($fp) {
				// prepare POST data
				$strPostData = '';
				foreach ($postData as $key => $value)
					$strPostData .= ($strPostData == '' ? '' : '&').$key.'='.urlencode($value);

				// prepare POST headers
				$str = "POST ".CRM_PATH." HTTP/1.0\r\n";
				$str .= "Host: ".CRM_HOST."\r\n";
				$str .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$str .= "Content-Length: ".strlen($strPostData)."\r\n";
				$str .= "Connection: close\r\n\r\n";

				$str .= $strPostData;

				// send POST to CRM
				fwrite($fp, $str);

				// get CRM headers
				$result = '';
				while (!feof($fp)) $result .= fgets($fp, 128);
				fclose($fp);

				// cut response headers
				$response = explode("\r\n\r\n", $result);

				$output = '<pre>'.print_r($response[1], 1).'</pre>';
			} else {
				echo 'Connection Failed! '.$errstr.' ('.$errno.')';
			}
		} else  $output = '';

		// if(!count($fields)) return self::AppendError('send.admin.mail.not.fields');
		// }else self::AppendError('template.admin.not.exist');
		// if($send_result !== true) self::AppendError('send.admin',$send_result);
		// return self::IsNotError();
		return $this;
	}
}