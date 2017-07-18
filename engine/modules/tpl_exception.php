<?php

/**
* Исколючение обработчика шаблонов
*
* (c) Zolotov Alex, 2009
*     zolotov-alex@shamangrad.net
*     http://shamangrad.net/
*
* Даже жалко для такой мелочи создавать отдельный файл =)
*/
class tpl_exception extends Exception
{
	/**
	* Путь к шаблону
	*/
	protected $template;
	
	/**
	* Номер строки
	*/
	protected $lineNo;
	
	/**
	* Конструктор исключения
	*/
	public function __construct($template, $line, $message)
	{
		parent::__construct("Template error: $message\nTemplate: {$template}\nLine: {$line}");
		$this->template = $template;
		$this->lineNo = $line;
	}
	
	/**
	* Вернуть шаблон в котором обнаружена ошибка
	*/
	public function getTemplate()
	{
		return $this->template;
	}
	
	/**
	* Вернуть номер строки в которой обнаружена ошибка
	*/
	public function getLineNo()
	{
		return $this->lineNo;
	}
}

?>