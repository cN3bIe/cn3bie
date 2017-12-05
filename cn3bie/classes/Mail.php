<?php

cn3bie::_inc('vendor/phpmailer/phpmailerautoload.php');
class Mail{
	private static $server='';
	private static $name='Ilrusstroy';
	private static $type='';
	private static $mail=null;
	private static $email='';
	private static $password='';
	private function __construct(){}
	private function __clone(){}
	private static function init($emailFrom=''){
		self::$mail = new PHPMailer();
		self::$mail->setLanguage('ru');
		self::$mail->CharSet = 'UTF-8';
		self::$mail->isHTML(true);
		switch(self::$type){
			case 'smtp':self::$mail->isSMTP();break;
			default: self::$mail->isMAIL();break;
		}

		self::$server=$_SERVER['HTTP_HOST'];
		self::$email=self::$name.'@'.$_SERVER['HTTP_HOST'];

		self::$mail->From = $emailFrom?$emailFrom:self::$email;
		self::$mail->FromName = self::$name;
		self::$mail->SMTPDebug = 0;
		self::$mail->Debugoutput = 'html';
		switch(self::$server){
			case 'smtp.gmail.com':
			self::$mail->Host = 'smtp.gmail.com';
			self::$mail->Port = 587;
			self::$mail->SMTPSecure = 'tls';
			break;
			case 'smtp.yandex.ru':
			self::$mail->Host = 'smtp.yandex.ru';
			self::$mail->Port = 465;
			self::$mail->SMTPSecure = 'ssl';
			break;
			case 'smtp.mail.ru':
			self::$mail->Host = 'smtp.mail.ru';
			self::$mail->Port = 465;
			self::$mail->SMTPSecure = 'ssl';
			break;
			default:
			self::$mail->Host = self::$server;
			self::$mail->Port = 25;
			break;
		}
		if(self::$password){
			self::$mail->SMTPAuth = true;
			self::$mail->Username = self::$email;
			self::$mail->Password = self::$password;
		}
		return self::$mail;
	}
	public static function sendAttach($file,$fileName, $to, $subject, $body, $from=''){
		self::init($from)->addAttachment($file,$fileName);
		if(is_array($to)){
			foreach($to as $key=>$mailTo){
				self::$mail->addAddress($mailTo, '');
			}
		}else self::$mail->addAddress($to, '');
		self::$mail->Subject = $subject;
		self::$mail->Body = $body;
		self::$mail->AltBody = trim(strip_tags($body));
		if(!self::$mail->send()) $resp = self::$mail->ErrorInfo;
		else $resp = true;

		return $resp;
	}
	public static function send($to, $subject, $body, $from='') {
		self::init($from);
		if(is_array($to)){
			foreach($to as $key=>$mailTo){
				self::$mail->addAddress($mailTo, '');
			}
		}else self::$mail->addAddress($to, '');
		self::$mail->Subject = $subject;
		self::$mail->Body = $body;
		self::$mail->AltBody = trim(strip_tags($body));
		if(!self::$mail->send()) $resp = self::$mail->ErrorInfo;
		else $resp = true;

		return $resp;
	}
}
