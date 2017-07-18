<?php

/**
* Экспериментальная реализация XML-RPC для WScore/LightEngine
*
* (c) Zolotov Alex, 2008,2011
*     zolotov-alex@shamangrad.net
*     http://shamangrad.net/
*/
class mod_rpc extends LightModule
{
	/**
	* Текущий пользователь
	*/
	private $user;
	
	public function __construct(LightEngine $engine)
	{
		parent::__construct($engine);
		$this->user = false;
	}
	
	/**
	* Конструктор модуля
	*
	* Читает конфиг, ищет реальный драйвер БД, загружает и возращает его
	*
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_rpc($engine);
	}
	
	/**
	* Авторизовать пользователя
	*/
	public function authorize()
	{
		if ( ! $this->user )
		{
			header('WWW-Authenticate: Basic realm="OTS RPC"');
			header('HTTP/1.0 401 Unauthorized');
			header('Content-type: text/xml');
			echo $this->encodeFault(2, $this->lang->format('std:login_fault'));
		}
	}
	
	public function getCurrentUser()
	{
		return $this->user;
	}
	
	public function setCurrentUser($user)
	{
		$this->user = $user;
	}
	
	/**
	* Закодировать сообщение об ошибке
	* @param $code код ошибки
	* @param $message сообщение об ошибке
	* @return XML-документ - XML-RPC-ответ
	*/
	public function encodeFault($code, $message)
	{
		$xml = new XML();
		$xml->open('methodResponse');
		$xml->open('fault');
		$xml->open('value');
		$this->encodeValue($xml, array(
		'faultCode' => intval($code),
		'faultString' => strval($message)
		));
		$xml->close('value');
		$xml->close('fault');
		$xml->close('methodResponse');
		return $xml->getContent();
		return "<?xml version=\"1.0\"?><methodResponse><fault><value>$value</value></fault></methodResponse>";
	}
	
	/**
	* Закодировать RPC-ответ
	* @param $result возвращаемое значение
	* @return XML-документ - XML-RPC-ответ
	*/
	public function encodeResponse($result)
	{
		$xml = new XML();
		$xml->open('methodResponse');
		$xml->open('params');
		$xml->open('param');
		$value = $this->encodeValue($xml, $result);
		$xml->close('param');
		$xml->close('params');
		$xml->close('methodResponse');
		return $xml->getContent();
		return "<?xml version=\"1.0\"?><methodResponse><params><param>$value</param></params></methodResponse>";
	}
	
	/**
	* Закодировать значение
	*/
	public function encodeValue($xml, $value)
	{
		switch ( gettype($value) )
		{
		case 'boolean':
			$xml->tag("boolean", $value ? 1 : 0);
			return;
		case 'integer':
			$xml->tag("int", $value);
			return;
		case 'double':
			$xml->tag("double", $value);
			return;
		case 'string':
			$xml->tag("string", $value);
			return;
		case 'array':
			$this->encodeArray($xml, $value);
			return;
		case 'object':
			if ( is_a($value, 'RPCBinary') )
			{
				$xml->tag("base64", base64_encode($value->data));
			}
			else if ( is_a($value, 'RPCDateTime') )
			{
				$xml->tag("dateTime.iso8601", gmdate('Ymd', $value->data).'T'.gmdate('H:i:s', $value->data));
			}
			return;
		case 'NULL':
			$xml->tag("boolean", 1);
			return;
		default: throw new Exception("unexpected type: " . gettype($value));
		}
	}
	
	/**
	* Закодировать массив/структуру
	*/
	public function encodeArray($xml, $array)
	{
		reset($array);
		list($key, $value) = each($array);
		if ( is_numeric($key) )
		{ // массив
			$xml->open('array');
			$xml->open('data');
			foreach ($array as $item)
			{
				$xml->open('value');
				$this->encodeValue($xml, $item);
				$xml->close('value');
			}
			$xml->close('data');
			$xml->close('array');
		}
		else
		{ // структура
			$xml->open('struct');
			foreach ($array as $key => $item)
			{
				$xml->open('member');
				$xml->tag('name', $key);
				$xml->open('value');
				$this->encodeValue($xml, $item);
				$xml->close('value');
				$xml->close('member');
			}
			$xml->close('struct');
		}
	}
}

?>