<?php
function get_page_link( $id ){
	return '/polit/';
}
function ot_get_option( $str ){
	switch( $str ){
		case 'main_phone': return '+7 (812) 920-25-76';
		default: return $str;
	}
}
function get_template_directory_uri(){
	return rtrim( cn3bie::get_theme_dir_url(), '/');
}
function get_img_path(){
	return cn3bie::get_img_dir_url();
}
function the_img_path(){
	echo get_img_path();
}
function get_the_post_thumbnail_url($id = 0, $size = ''){
	return rtrim( get_img_path(), '/').'/materials/blog.jpg';
}
function get_the_ID(){
	return 0;
}

function language_attributes(){
	echo '';
}
function bloginfo( $type ){
	switch( $type ){
		case 'charset':echo 'UTF-8';
		default: echo '';
	}
}
function menu_cn3bie(){
	?><li class="li-d li-menu"><a href="/projects/" class="a-d a-menu">Проекты</a></li><?php
	?><li class="li-d li-menu"><a href="/clients/" class="a-d a-menu">Клиентам</a></li><?php
	?><li class="li-d li-menu"><a href="/services/" class="a-d a-menu">Услуги</a></li><?php
	?><li class="li-d li-menu"><a href="/stroim/" class="a-d a-menu">Строим</a></li><?php
	?><li class="li-d li-menu"><a href="/gallery/" class="a-d a-menu">Построили</a></li><?php
	?><li class="li-d li-menu"><a href="/reviews/" class="a-d a-menu">Отзывы</a></li><?php
	?><li class="li-d li-menu"><a href="/caloborations/" class="a-d a-menu">Участки</a></li><?php
	?><li class="li-d li-menu"><a href="/contacts/" class="a-d a-menu">Контакты</a></li><?php
}

function get_title($title=''){
	return cn3bie::title($title);
}
function the_title($title=''){
	echo get_title($title);
}

function wp_head(){
	echo '<title>'.get_title().'</title>';
	ScriptCSS::echoCSS();
}
function wp_footer(){
	ScriptCSS::echoScript();
}
function get_body_class($class=''){
	return cn3bie::body_class($class);
}
function body_class($class=''){
	echo 'class="'.get_body_class($class).'"';
}
function get_template_part($name,$params=array()){
	cn3bie::shortcodes($name,$params);
}
function get_header(){
	cn3bie::inc(cn3bie::get_theme_dir().'header.php');
}
function get_footer(){
	cn3bie::inc(cn3bie::get_theme_dir().'footer.php');
}

function cn3bie_price_style( $price ){
	return number_format( $price ,0,',',' ' );
}

function Redirect($str='/'){
	if(!headers_sent()) header('Location: '.$str);
	else die('<meta http-equiv="refresh" content="0;'.$str.'">');
	die();
}
