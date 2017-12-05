<?php
class User{
	private $table = 'cn3bie_user';
	private static $instance=null;
	private $db=null;
	private function __constructor(){}
	private function __clone(){}
	private static function getInstance(){
		session_start();
		if(is_null(self::$instance)){
			self::$instance = new self();
		}
		return self::$instance;
	}
	public static function init(){
		session_start();
		if(isset($_POST['event']) && $_POST['event']==='login'){
			$user=db::clear($_POST['login']);
			$pass=db::clear($_POST['pass']);
			if($user && $pass) self::Login($user,md5($pass));
			Redirect('/profile/');
		}
	}
	public static function Login($login,$pass){
		$query = db::init()->prepare("SELECT * FROM cn3bie_user WHERE login = :login AND password = :password");
		$query->execute(array(
			':login'    => $login,
			':password' => $pass
			));
		$item = array_shift($query->fetchAll());
		if($item){
			session_start();
			$_SESSION['user']=array(
				'id'=>$item['id'],
				'login'=>$item['login'],
				'ava'=>$item['ava'],
				'type'=>$item['type'],
				'email'=>$item['email'],
				'active_pay'=>$item['active_pay'],
				'description'=>$item['description']
			);
			db::update('cn3bie_user', array('last_login' => date('Y-m-d H:i:s')), 'id='.$item['id']);
			return true;
		}
		return false;
	}
	/**
	 * @param $security - Проверяет работу авторизации если вход был и переменная равна 1 тогда продолжим если нет то Redirect иfalse
	 * @param $data - Массив сессионных параметров нужный для получения всей информации для конкретного пользователя
	 */
	public function getData($security, $data) {
		if($security == '1') {
			Connect();
			$query = $db->prepare("SELECT * FROM {$this->table} WHERE login = :login");
			$query->execute(array(':login' => $data ));
			if($query->rowCount() > 0) {
				$itemses = $query->fetch(PDO::FETCH_OBJ);
				return $itemses;
			} else return false;
		} else Redirect('/login/');
	}
	public function rePassword($data) {
		$db = db::getConnect();
		$query = $db->prepare("SELECT * FROM {$this->table} WHERE u_email = :email");
		$query->execute(array(':email' => $data['email']));
		$item = $query->fetch(PDO::FETCH_OBJ);
		// данные о пользователе из Базы
		return $item;
	}
	public static function update($_data){
		$data=array();
		if($_data===$_POST){
			if(db::clear($_data['login'])) $data['login']=db::clear($_data['login']);
			if(db::clear($_data['pass'])) $data['password']=md5(db::clear($_data['pass']));
			if(db::clear($_data['email'])) $data['email']=db::clear($_data['email']);
			if(db::clear($_data['description'])) $data['description']=db::clear($_data['description']);
		}elseif($_data===$_GET){
			if(db::clear($_data['ava'])) $data['ava']=db::clear($_data['ava']);
		}
		if(count($data)>0) db::update('cn3bie_user', $data,'id='.User::info('id'));
		if($data['ava']) $_SESSION['user']['ava']=$data['ava'];
		if($data['login']) $_SESSION['user']['login']=$data['login'];
		if($data['email']) $_SESSION['user']['email']=$data['email'];
		if($data['description']) $_SESSION['user']['description']=$data['description'];
	}
	public static function registr($login,$mail,$pass){
		db::insert('cn3bie_user', array(
			'login'    => $login,
			'password' => md5($pass),
			'reg_date' => date('Y-m-d H:i:s'),
			'active_pay' => '0',
			'type'    => 'user',
			'email' => $email,
			'r_password' =>$pass
		));
	}
	public static function info($str=''){
		session_start();
		if(!$str) return isset($_SESSION['user']);
		return $_SESSION['user'][$str];
	}
	public function logout(){
		session_start();
		unset($_SESSION['user']);
		Redirect('/login/');
		die();
	}
	public static function type(){
		session_start();
		return $_SESSION['user']['type'];
	}
}