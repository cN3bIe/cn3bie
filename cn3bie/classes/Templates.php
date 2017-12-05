<?php
class Templates{
	private static $data=array();
	private static $header_path='header.php';
	private static $footer_path='footer.php';
	private static $fh_dir='';
	// Название темы
	private static $tpl_name='default';
	// Путь к папке с темами
	private static $tpl_path_dir='templates/';
	// Полный путь к шаблону
	private static $tpl_path_template='';
	// Массив подключаемых файлов в теме
	private static $tpl_include_file=array();
	//Объект скриптов и стилей
	private static $ScriptCSS=null;

	private static $_instance = null;
	private function __constructor(){}
	static public function getInstance(){
		if(is_null(self::$_instance)){
			self::$_instance=new self();
		}
		return self::$_instance;
	}
	private function __clone(){}
	static public function init($name='',$dir='',$tpl_include_file=''){
		cn3bie::incClass('ScriptCSS');
		if($name) self::$tpl_name=$name;
		if($tpl_include_file) self::$tpl_include_file=$tpl_include_file;
		self::$tpl_path_dir=$dir?$dir:cn3bie::getRootKernelDir().self::$tpl_path_dir;
		self::$tpl_path_template=self::$tpl_path_dir.self::$tpl_name.'/';
		self::$fh_dir=self::$tpl_path_template;
		require_once(rtrim(self::$tpl_path_dir,'/').'/'.ltrim(self::$tpl_name.'/template.php','/'));
		return self::getInstance();
	}
	//Добавление header
	static public function add_header($path){self::$header_path=$path;}
	static public function add_footer($path){self::$footer_path=$path;}
	//Добавление в массив подключаемых файлов шаблона
	static public function add_tpl($path){
		$path_file=self::$tpl_path_template.ltrim($path,'/');
		if(file_exists($path_file)) array_push(self::$tpl_include_file,$path_file);
	}
	public static function content_page(){
		self::$tpl_path_template.='page'.'/';
		$url_info=parse_url($_SERVER['REQUEST_URI']);
		$url_path=trim($url_info['path'],'/');
		$url_path=$url_path?str_replace('.php','',$url_path):'index';
		$url_path_array=explode('/',$url_path);
		if(file_exists(self::$tpl_path_template.$url_path.'.php')) Templates::add_tpl($url_path.'.php');
		elseif(file_exists(self::$tpl_path_template.$url_path.'.inc.php')) require_once(self::$tpl_path_template.$url_path.'.inc.php');
		elseif(file_exists(self::$tpl_path_template.$url_path.'/index.php')) Templates::add_tpl($url_path.'/index.php');
		elseif(file_exists(self::$tpl_path_template.$url_path.'/template.php')) require_once(self::$tpl_path_template.$url_path.'/template.php');
		else{
			$error404=true;
			foreach($url_path_array as $key=>$value){
				$path=self::$tpl_path_template.implode('/',$url_path_array).'/template.php';
				if(file_exists($path)){
					require_once($path);
					$error404=false;
					break;
				}else array_pop($url_path_array);
			}
			if($error404) self::Page404();
		}
	}
	private function inc($path){
		if(file_exists($path)) require_once($path);
	}
	public static function url($str=''){
		$url=parse_url($_SERVER['REQUEST_URI']);
		if($str) return $url[$str];
		else return $url;
	}
	// Построение шаблона
	public function build(){
		if(!isset($_POST['events'])) $_SESSION['referer']=$_SERVER['REQUEST_URI'];
		self::data();
		self::inc(self::$fh_dir.self::$header_path);
		foreach(self::$tpl_include_file as $key=>$path){
			self::inc($path);
		}
		if(function_exists('tpl')) tpl();
		self::inc(self::$fh_dir.self::$footer_path);
		return self::getInstance();
	}
	public static function dir(){
		return str_replace(cn3bie::getRootDir(),'',self::$tpl_path_dir).self::$tpl_name.'/';
	}
	public static function dirTemplate(){
		return self::$tpl_path_dir.self::$tpl_name.'/';
	}
	public function info(){
		echo '<xmp>';
		var_dump(self::$header_path);
		var_dump(self::$footer_path);
		var_dump(self::$tpl_name);
		var_dump(self::$tpl_path_dir);
		var_dump(cn3bie::getRootDir());
		var_dump(self::$tpl_include_file);
		var_dump(self::$tpl_path_template);
		echo '</xmp>';
	}
	public static function Page404(){
		header("HTTP/1.0 404 Not Found");
		if(file_exists(self::$fh_dir.'404.php')) require_once(self::$fh_dir.'404.php');
		elseif(file_exists(cn3bie::getRootKernelDir().'404.php')) require_once(cn3bie::getRootKernelDir().'404.php');
		die();
	}
	public static function data(){
		$end_url = str_replace('/','',$_SERVER['REQUEST_URI']);
		switch($end_url){
			case 'catalog': self::$data=array('title'=>'Каталог','class'=>$end_url); break;
			case 'catalogcard': self::$data=array('title'=>'Проект','class'=>$end_url); break;
			case 'news': self::$data=array('title'=>'Новости','class'=>$end_url); break;
			case 'technologi': self::$data=array('title'=>'О технологии','class'=>$end_url); break;
			case 'treatment_facilities': self::$data=array('title'=>'Очистные сооружения','class'=>$end_url); break;
			case 'svai': self::$data=array('title'=>'Винтовые сваи','class'=>$end_url); break;
			case 'contacts': self::$data=array('title'=>'Контакты','class'=>$end_url); break;
			default: self::$data=array('title'=>'Домашняя','class'=>$end_url); break;
		}
	}
	public static function title($title=''){
		return ($title?$title:self::$data['title']);
	}
	public static function body_class($class=''){
		return ($class?self::$data['class'].' '.$class:self::$data['class']);
	}
}