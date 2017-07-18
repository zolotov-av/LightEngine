<?php

/**
* Исключение драйвера базы данных
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2009
*
* @package mod_db
*/
class db_exception extends Exception
{
	/**
	* Класс драйвера БД
	*/
	protected $driver;
	
	/**
	* Запрос который вызвал ошибку или пустая строка
	*/
	protected $query;
	
	/**
	* Сообщение об ошибке которое вернул драйвер или пустая строка
	*/
	protected $driverMessage;
	
	/**
	* Конструктор исключения
	* @param string сообщение об ошибке
	* @param string драйвер БД
	* @param string запрос который вызвал ошибку
	* @param string сообщение об ошибке от драйвера
	*/
	public function __construct($message, $driver, $query, $driverMessage)
	{
		parent::__construct("Database error: $message");
		$this->driver = $driver;
		$this->query = $query;
		$this->driverMessage = $driverMessage;
	}
	
	public function getDriver()
	{
		return $this->driver;
	}
	
	public function getQuery()
	{
		return $this->query;
	}
	
	public function getDriverMessage()
	{
		return $this->driverMessage;
	}
}

?>