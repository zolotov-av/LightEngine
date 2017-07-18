<?php

/**
* Модуль логирования
*
* Данный модуль отправляет syslog-сообщения по протоколу syslog
* на удаленный syslog-сервер
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2012
*/
class mod_netlog extends LightModule
{
	/**
	* IP-адрес syslog-сервера
	*/
	private $host;
	
	/**
	* Порт syslog-сервера
	*/
	private $port;
	
	/**
	* Сокет через который будет производиться отправка
	*/
	private $socket;
	
	/**
	* Префикс который будет добавляться к каждому отправляемому сообщению
	*/
	private $prefix;
	
	/**
	* Конструктор модуля
	*
	* IP-адрес syslog-сервера берется из константы MOD_NETLOG_HOST
	* Порт сервера берется из константы MOD_NETLOG_PORT
	*
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_netlog($engine);
	}
	
	/**
	* Конструктор модуля
	*/
	public function __construct(LightEngine $engine)
	{
		parent::__construct($engine);
		$this->host = defined('MOD_NETLOG_HOST') ? MOD_NETLOG_HOST : '127.0.0.1';
		$this->port = defined('MOD_NETLOG_PORT') ? MOD_NETLOG_PORT : 514;
		$this->prefix = defined('MOD_NETLOG_PREFIX') ? MOD_NETLOG_PREFIX : '';
		$this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		$this->write("netlog start");
	}
	
	/**
	* Деструктор модуля
	*/
	public function __destruct()
	{
		$this->write("netlog end");
		socket_close($this->socket);
	}
	
	/**
	* Добавить запись в журнал
	* @param $message текстовое сообщение
	*/
	public function write($message)
	{
		$text = $this->prefix . $message;
		$len = strlen($text);
		socket_sendto($this->socket, $text, $len, 0, $this->host, $this->port);
	}
}

?>