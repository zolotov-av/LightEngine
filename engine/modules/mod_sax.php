<?php

/**
* Вспомогательный модуль для парсинга XML-файлов через SAX
*
* Модуль mod_sax использует расширение доступное в PHP по-умолчанию,
* если поддержка XML не отключена ключём --disable-xml
*
* Этот модуль является абстрактым, поэтому не содержит конструктора
*
* (c) Zolotov Alex, 2007-2008
*     zolotov-alex@shamangrad.net
*     http://shamangrad.net/
*/
abstract class mod_sax extends LightModule
{
	/**
	* SAX парсер
	*/
	protected $parser;
	
	/**
	* Разделитель префикса пространства имен XML
	*/
	protected $ns_delimiter = ':';
	
	/**
	* Атрибуты тегов
	*/
	protected $attrs;
	
	/**
	* Буфер для накопления текстовых данных
	* @see catchText() и getCatchedText()
	*/
	protected $text;
	
	/**
	* Признак захвата текста
	* @see catchText() и getCatchedText()
	*/
	protected $catch_text;
	
	/**
	* Конструктор SAX-парсера
	*
	* @note Если потомок переопределяет конструктор, то он обязан вызывать
	* родительский конструктор: parent::__construct();
	*/
	public function __construct()
	{
		$this->parser = xml_parser_create();
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, "on_start_element", "on_end_element");
		xml_set_character_data_handler($this->parser, "on_cdata");
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, 0);
		xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
	}
	
	/**
	* Деструктор парсера
	*
	* @note Если потомок переопределяет деструктор, то желательно, чтобы
	* потомк вызывал родительский: parent::__destruct()
	*/
	public function __destruct()
	{
		xml_parser_free($this->parser);
	}
	
	/**
	* Сброс парсера на начальные настройки
	*/
	public function reset()
	{
		xml_parser_free($this->parser);
		$this->parser = xml_parser_create();
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, "on_start_element", "on_end_element");
		xml_set_character_data_handler($this->parser, "on_cdata");
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, 0);
		xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
	}
	
	/**
	* Установить разделитель префикса пространства имен XML
	* 
	* Перед вызовом callback стандартный разделитель ':' будет заменяться
	* на указанный здесь
	* 
	* @param string разделитель
	*/
	public function setNSDelimiter($delimiter)
	{
		$this->ns_delimiter = $delimiter;
	}
	
	/**
	* Парсинг очередного куска XML-файла
	* @param string кусок XML-файла для парсига
	* @param bool признак последнего куска
	* @return bool TRUE - успешно, FALSE - произошла ошибка
	*/
	public function parse($data, $isFinal = true)
	{
		return xml_parse($this->parser, $data, $isFinal);
	}
	
	/**
	* Вернуть код ошибки
	* @return bool код ошибки
	*/
	public function errno()
	{
		return xml_get_error_code($this->parser);
	}
	
	/**
	* Вернуть сообщение об ошибке
	* @return string сообщение об ошибке
	*/
	public function error()
	{
		return xml_error_string(xml_get_error_code($this->parser));
	}
	
	/**
	* Начать захват текста
	*/
	protected function catchText()
	{
		$this->catch_text = true;
		$this->text = '';
	}
	
	/**
	* Закончить захват текста
	* @return string захваченый текст
	*/
	protected function getCatchedText()
	{
		if ( $this->catch_text )
		{
			$result = $this->text;
			$this->text = null;
			$this->catch_text = false;
			return $result;
		}
		return null;
	}
	
	/**
	* Обработка символьных данных
	* @param resource парсер
	* @param string текстовые данные
	*/
	protected function on_cdata($parser, $data)
	{
		if ( $this->catch_text )
		{
			$this->text .= $data;
		}
	}
	
	/**
	* Обработка открывающегося тега
	* @param resource парсер
	* @param string имя открывающегося тега
	* @param array атрибуты тега
	*/
	protected function on_start_element($parser, $name, $attrs)
	{
		$name = str_replace(':', $this->ns_delimiter, $name);
		if ( method_exists($this, "catch_{$name}_tag") )
		{
			$this->attrs = $attrs;
			$this->catchText();
		}
		elseif ( method_exists($this, $method = "start_{$name}_tag") )
		{
			call_user_func(array(& $this, $method), $attrs);
		}
		else
		{
			$this->start_element($name, $attrs);
		}
	}
	
	/**
	* Обработка закрывающегося тега
	* @param resource парсер
	* @param string имя закрывающегося тега
	*/
	protected function on_end_element($parser, $name)
	{
		if ( method_exists($this, $method = "catch_{$name}_tag") )
		{
			$text = $this->getCatchedText();
			call_user_func(array(& $this, $method), $text, $this->attrs);
		}
		elseif ( method_exists($this, $method = "end_{$name}_tag") )
		{
			call_user_func(array(& $this, $method));
		}
		else
		{
			$this->end_element($name);
		}
	}
	
	/**
	* Обработка открывающегося тега
	* @param string имя открывающегося тега
	* @param array атрибуты открывающегося тега
	*/
	protected function start_element($name, $attrs)
	{
	}
	
	/**
	* Обработка закрывающегося тега
	* @param string имя закрывающегося тега
	*/
	protected function end_element($name)
	{
	}
}

?>