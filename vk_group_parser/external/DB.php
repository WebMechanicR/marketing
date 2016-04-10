<?php
/**
 * класс для работы с базой
 * @author riol
 *
 */

require_once dirname(__FILE__).'/DbSimple/Connect.php';

class DB extends DbSimple_Connect  {
	
	/**
	 * количество запросов к базе
	 */
	public static $num_queries = 0;
	
	/**
	 * соединение с базой
	 */
	public function __construct($dsn = false) {
                $dsn = ($dsn)?$dsn:"mypdo://".DB_USER.":".DB_PASSWORD."@".DB_HOST."/".DB_NAME."?enc=UTF8";
		parent::__construct($dsn);
		parent::setIdentPrefix(DB_PREFIX_);
	} 
	
	public function mySqlLogger($db, $sql) {
		self::$num_queries++;
	}
	
	public function get_num_queries() {
		return self::$num_queries/2;
	}
}