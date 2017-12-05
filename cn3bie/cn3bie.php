<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);
class cn3bie{
	protected static $url = '';

	protected static $dir = '';
	protected static $dir_cn3bie = '';
	protected static $dir_vendor = '';
	protected static $dir_tpl = '';
	protected static $theme_name ='default';
	protected static $dir_theme ='';
	protected static $dir_page ='';
	protected static $dir_img = '';

	protected static $settings = array();

	protected static $log = '';
	protected static $_instance=null;
	protected function __construct(){
		$this->kernel();
	}
	protected function __clone(){}
	public static function init(){
		if(is_null(self::$_instance)){
			self::$_instance=new self();
		}
		return self::$_instance;
	}


	/*Includes*/
	static public function _inc($path){
		$path=self::$dir_cn3bie.$path;
		if( file_exists($path) ){
			require_once($path);
			$result='File';
			$return = !0;
		}else{
			$result='Not file';
			$return = !1;
		}
		return self::wlog('inc',$result.':'.$path, $return);
	}
	static public function inc($path){
		if( file_exists($path) ){
			require_once($path);
			$result='File';
			$return = !0;
		}else{
			$result='Not file';
			$return = !1;
		}
		return self::wlog('inc',$result.':'.$path, $return);
	}


	public static function get_dir(){return self::$dir;}
	public static function get_cn3bie_dir(){return self::$dir_cn3bie;}
	public static function get_vendor_dir(){return self::$dir_vendor;}
	public static function get_tpl_dir(){return self::$dir_tpl;}
	public static function get_theme_dir(){return self::$dir_theme;}
	public static function get_theme_dir_url(){return '/'.str_replace(self::$dir,'',self::$dir_theme);}
	public static function get_page_dir(){return self::$dir_page;}
	public static function get_img_dir(){return self::$dir_img;}
	public static function get_img_dir_url(){return '/'.str_replace(self::$dir,'',self::$dir_img);}

	public static function get_json_theme( $path ){
		$path_theme = self::get_theme_dir().'/'.trim( $path, '/' );
		if( file_exists($path_theme) ){
			$result='File';
			$return = json_decode(file_get_contents( $path_theme ));
		}else{
			$result='Not file';
			$return = null;
		}
		return self::wlog('get_json_theme',$result.":\t".$path_theme, $return);
	}


	public function template( $template_name = ''){
		self::$theme_name = $template_name?$template_name:self::$theme_name;
		if( !isset( $_POST['events']) ) $_SESSION['referer'] = $_SERVER['REQUEST_URI'];

		self::$dir_theme = self::$dir_tpl.self::$theme_name.'/';
		self::$dir_page = self::$dir_theme.'page/';
		self::$dir_img = self::$dir_theme.'img/';

		return $this;
	}

	public function build(){
		self::inc( self::$dir_theme.'functions.php' );
		$url_path=trim(self::$url,'/');
		$url_path=$url_path?str_replace('.php','',$url_path):'index';
		$url_path_array=explode('/',$url_path);
		$path_file = self::$dir_page.$url_path;
		if( !self::route( $url_path ) ) self::Page404();
		return $this;
	}
	static protected function route( $path ){
		$path_arr = explode('/',$path);
		foreach($path_arr as $key){
			$path = self::get_page_dir().implode('/',$path_arr);
			$ret = !1;
			if( self::inc( $path.'.php' ) ) $ret = !0;
			elseif( self::inc( $path.'/index.php') ) $ret = !0;
			else array_pop($path_arr);
		}
		return $ret;
	}
	static public function settings(){
		$end_url = str_replace('/','', self::$url );
		switch($end_url){
			case 'polit': self::$settings = array('title'=>'Политика конфедециальности','class'=>$end_url); break;
			case 'catalog': self::$settings = array('title'=>'Политика конфедециальности','class'=>$end_url); break;
			default: self::$settings = array('title'=>'Строитительство домов в Санкт-Петербурге и области','class'=>'home'); break;
		}
	}

	public static function Page404(){
		header("HTTP/1.0 404 Not Found");
		if( !self::inc( self::$dir_theme.'404.php') )
			if( !self::inc(self::$dir.'404.php') ) echo 'Все плохо!';
		die();
	}


	static public function title($title=''){
		return ($title?$title:self::$settings['title']);
	}
	static public function body_class($class=''){
		return ($class?self::$settings['class'].' '.$class:self::$settings['class']);
	}

	static public function shortcodes($path,$args=array()){
		$path = str_replace('.php','',$path);
		if(
			is_array($args)
			&&
			count($args)
			&&
			is_array($args['page'])
			&&
			count($args['page'])
			&&
			isset($args['page'][$_SERVER['REQUEST_URI']])
			&&
			!$args['page'][$_SERVER['REQUEST_URI']]
		) return self::wlog('shortcodes','Not include - '.$path.'| page['.$_SERVER['REQUEST_URI'].']',!0);
		$path=self::get_theme_dir().$path.'.php';
		if(file_exists($path)){
			require($path);
			$result='File';
		} else $result='Not file';
		self::wlog('shortcodes',$result.':'.$path);
	}
	public static function incClass($name){
		self::wlog('incClass',$name);
		self::_inc('classes/'.$name.'.php');
	}
	public static function incVendor($name){
		self::wlog('incVendor',$name);
		self::_inc('vendor/'.$name);
	}
	protected function kernel(){
		self::wlog('Build Kernel','Begin....');
		session_start();
		self::$url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
		self::$dir = rtrim( $_SERVER['DOCUMENT_ROOT'],'/' ).'/';
		self::$dir_cn3bie = self::$dir.'cn3bie/';
		self::$dir_vendor = self::$dir_cn3bie.'vendor/';
		self::$dir_tpl = self::$dir_cn3bie.'templates/';
		self::settings();
		self::incClass('ScriptCSS');
		self::_inc('config/config.php');
		self::_inc('config/tools.php');
		self::wlog('Build Kernel','....end!');
	}

	/*LOGGER*/
	protected static function wlog( $block, $message, $return = true){
		self::$log.='<h3>'.$block.'</h3>'."\n";
		self::$log.='<div>'.$message.'</div>'."\n";
		return $return;
	}
	public static function getLog(){
		echo self::$log;
	}
	static public function vd($name,$val){
		// echo '<div><b>'.$name.'</b>: ';
		var_dump($val);
		// echo "</div>\n";
	}
	static public function info(){
		self::getLog();
		self::vd( 'url',self::$url );
		self::vd( 'theme_name',self::$theme_name );
		self::vd( 'settings',self::$settings );
		self::vd( 'get_dir',self::get_dir() );
		self::vd( 'get_cn3bie_dir',self::get_cn3bie_dir() );
		self::vd( 'get_vendor_dir',self::get_vendor_dir() );
		self::vd( 'get_tpl_dir',self::get_tpl_dir() );
		self::vd( 'get_theme_dir',self::get_theme_dir() );
		self::vd( 'get_theme_dir_url',self::get_theme_dir_url() );
		self::vd( 'get_page_dir',self::get_page_dir() );
		self::vd( 'get_img_dir',self::get_img_dir() );
		self::vd( 'get_img_dir_url',self::get_img_dir_url() );
	}
}
cn3bie::init()
	->template()
		->build();
// echo '<!--';
// cn3bie::info();
// echo '-->';