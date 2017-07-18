<?php

/**
* Парсер XML-RPC-запросов
*
* (c) Zolotov Alex, 2008,2011
*     zolotov-alex@shamangrad.net
*     http://shamangrad.net/
*/
class mod_XMLRPCParser extends mod_sax
{
	/**
	* Вызываемый метод
	*/
	private $method;
	
	/**
	* Параметры
	*/
	private $params;
	
	/**
	* Имя текущего поля структуры
	*/
	private $member;
	
	/**
	* Стуктура
	*/
	private $struct;
	
	/**
	* Признак обработки массива
	*/
	private $readArray;
	
	/**
	* Элементы массива
	*/
	private $items;
	
	/**
	* Текущее значение
	*/
	private $value;
	
	private $stack;
	
	/**
	* Конструктор модуля
	*
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_XMLRPCParser($engine);
	}
	
	/**
	* Парсинг запроса
	* @param $text текст запроса
	* @return пара (метод, параметры)
	*/
	public function parseRequest($text)
	{
		$this->method = '';
		$this->params = array ();
		$this->readArray = false;
		$this->capture_text = false;
		$this->stack = array ();
		if ( ! $this->parse($text, true) )
		{
			throw new Exception('request parse fault', 1);
		}
		return array($this->method, $this->params);
	}
	
	/**
	* Обработка открывающегося тега
	* @param string имя открывающегося тега
	* @param array атрибуты открывающегося тега
	*/
	protected function start_element($name, $attrs)
	{
		switch ( $name )
		{
		case 'name':
		case 'methodName':
		case 'int':
		case 'i4':
		case 'double':
		case 'boolean':
		case 'string':
		case 'base64':
		case 'dateTime.iso8601':
			$this->catchText();
			break;
		case 'struct':
			array_push($this->stack, $this->readArray);
			$this->readArray = false;
			$this->struct = array ();
			break;
		case 'array':
			array_push($this->stack, $this->readArray);
			$this->readArray = true;
			$this->items = array ();
			break;
		}
	}
	
	protected function catch_int_tag($text)
	{
		$this->value = intval($text);
	}
	
	protected function catch_i4_tag($text)
	{
		$this->value = intval($text);
	}
	
	protected function catch_double_tag($text)
	{
		$this->value = floatval($text);
	}
	
	protected function catch_string_tag($text)
	{
		$this->value = strval($text);
	}
	
	protected function catch_base64_tag($text)
	{
		$this->value = base64_decode(strval($text));
	}
	
	protected function catch_boolean_tag($text)
	{
		$text = strval($text);
		$this->value = ($text === '1') or ($text === 'true');
	}
	
	protected function parse_date()
	{
		$this->value = parse_iso_date($this->getCatchedText());
	}
	
	protected function catch_name_tag($text)
	{
		$this->member = $text;
	}
	
	protected function catch_methodName_tag($text)
	{
		$this->method = $text;
	}
	
	protected function end_member_tag()
	{
		$this->struct[$this->member] = $this->value;
	}
	
	protected function end_struct_tag()
	{
		$this->value = $this->struct;
		$this->readArray = array_pop($this->stack);
	}
	
	protected function end_value_tag()
	{
		if ( $this->readArray )
		{
			$this->items[] = $this->value;
		}
	}
	
	protected function end_array_tag()
	{
		$this->value = $this->items;
		$this->readArray = array_pop($this->stack);
	}
	
	protected function end_param_tag()
	{
		$this->params[] = $this->value;
	}
	
	/**
	* Обработка закрывающегося тега
	* @param string имя закрывающегося тега
	*/
	protected function end_element($name)
	{
		if ( $name == 'dateTime.iso8601' )
		{
			$this->parse_date();
		}
	}
}

?>