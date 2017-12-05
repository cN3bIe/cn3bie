<?php
class ScriptCSS{
	private static $css=array();
	private static $script=array();
	public static function addCSS($str){
		if($str) self::$css[]=$str;
	}
	public static function addScript($str){
		if($str) self::$script[]=$str;
	}
	public static function add($obj){
		if(is_array($obj['CSS']) && count($obj['CSS'])) foreach($obj['CSS'] as $key=>$item){
			if(is_string($item)){
				if($item) self::addCSS($item);
			}elseif(is_array($item)) if(self::accessPage($item['page'])) if($item['href']) self::addCSS($item['href']);
		}
		if(is_array($obj['Script']) && count($obj['Script'])) foreach($obj['Script'] as $key=>$item){
			if(is_string($item)){
				if($item) self::addScript($item);
			}elseif(is_array($item) && count($item)) if(self::accessPage($item['page'])) if($item['src']) self::addScript($item['src']);
		}
	}
	public static function accessPage($page_array){
		if(isset($page_array[$_SERVER['REQUEST_URI']])) return (bool)$page_array[$_SERVER['REQUEST_URI']];
	}
	public static function echoCSS(){
		$return_css='';
		if(count(self::$css)) foreach(self::$css as $key=>$css){
			$return_css.='<link rel="stylesheet" type="text/css" href="'.str_replace(array('[%vendor%]','[%css%]'),array(cn3bie::get_theme_dir_url().'vendor',cn3bie::get_theme_dir_url().'css'),$css).'">';
		}
		echo $return_css;
	}
	public static function echoScript(){
		$return_script='';
		if(count(self::$script)) foreach(self::$script as $key=>$script){
			$return_script.='<script  type="text/javascript" src="'.str_replace(array('[%vendor%]','[%js%]'),array(cn3bie::get_theme_dir_url().'vendor',cn3bie::get_theme_dir_url().'js'),$script).'"></script>';
		}
		echo $return_script;
	}
}
