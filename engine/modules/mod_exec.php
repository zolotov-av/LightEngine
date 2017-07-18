<?php

/**
* Модуль для работы с внешними командами и скриптами
*
* (с) Золотов Алексей <zolotov-alex@shamangrad.net>, 2010
*/
class mod_exec extends LightModule
{
	/**
	* Код возврата
	*/
	public $code;
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	*/
	public function __construct(LightEngine $engine)
	{
		parent::__construct($engine);
	}
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_exec($engine);
	}
	
	/**
	* Экранировать спец. символы в строке
	* @param string исходный текст
	* @retval string экранированный текст
	*/
	public function escape($text)
	{
		return escapeshellcmd($text);
	}
	
	/**
	* Экранировать спец. символы в строке и заключить её в кавычки
	* @param string исходный текст
	* @retval string текст в кавычках
	*/
	public function quote($text)
	{
		return '"' . addslashes($text) . '"';
	}
	
	/**
	* Выполнить команду
	* @param string команда
	* @retval bool TRUE команда выполена успешно, FALSE произошла ошибка
	*/
	public function exec($command)
	{
		$args = implode(" ", array_map("escapeshellcmd", func_get_args()));
		exec($args, $output, $this->code);
		return $this->code == 0;
	}
	
	/**
	* Выполнить команду и вернуть вывод подобно функции file()
	* @param string команда
	* @retval array вывод команды в виде списка строк
	*/
	public function file($command)
	{
		$args = implode(" ", array_map("escapeshellcmd", func_get_args()));
		exec($args, $output, $this->code);
		return $output;
	}
	
	/**
	* Выполнить команду и прочитать вывод
	* @param string команда
	* @retval string вывод команды
	*/
	public function read($command)
	{
		$args = implode(" ", array_map("escapeshellcmd", func_get_args()));
		exec($args, $output, $this->code);
		return implode("\n", $output);
	}
}

?>