<?php
class DB{
	private static $db_host = 'localhost';
	private static $db_name = 'lpixel_bele';
	private static $db_user = 'todayistommorow';
	private static $db_pswd = 'r2U*01Tr';
	private static $_instance = null;
	public static function clear($str){
		return strip_tags(trim($str));
	}
	private function __constrict(){}
	private function __clone(){}
	public static function init(){
		if(!isset(self::$_instance)){
			try{
				self::$_instance = new PDO('sqlite:'.cn3bie::getRootDir().'/reliz');
			} catch (PDOException $e){
				throw new Exception("Ошибка соеденения с БД - ".$e->getMessage());
			}
		}
		return self::$_instance;
	}
	public static function insert($table, $data){
		ksort($data);
		$fieldName  = implode('`, `', array_keys($data)); // first key
		$fieldValue = ':' . implode(', :', array_keys($data)); // second key
		$query = self::init()->prepare("INSERT INTO $table (`$fieldName`) VALUES ($fieldValue)");
		foreach($data as $key => $value) $query->bindValue(":$key", $value);
		$query->execute();
	}
	public static function update($table, $data, $where){
		ksort($data);
		$fieldDetails = null;
		foreach($data as $key => $value) $fieldDetails .= "`$key`=:$key,";
		$fieldDetails = rtrim($fieldDetails, ','); # убираем последную запятую которая при запросе
		$sth = self::init()->prepare("UPDATE $table SET $fieldDetails WHERE $where");
		foreach($data as $key => $value) $sth->bindValue(":$key", $value);
		$sth->execute();
	}
	public static function getAllProjects(){
		$query = self::init()->prepare("SELECT * FROM house");
		$query->execute();
		return $query->fetchAll(PDO::FETCH_ASSOC);
	}
	public static function getItemById($table,$id){
		$query = self::init()->prepare("SELECT * FROM ".$table." WHERE id = :id");
		$query->execute(array(':id'=>$id));
		return $query->fetchAll(PDO::FETCH_ASSOC);
	}
	public static function del($table,$id,$where=''){
		$result = self::init()->exec('DELETE FROM '.$table.' WHERE id = '.$id.' '.$where);
		return $result;
	}
}