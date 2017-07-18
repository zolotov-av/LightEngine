<?php

/**
* MIME-сообщение
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2009
*
* @package mod_mime
*/
class MimeMessage
{
	/**
	* Заголовки сообщения
	* @var array
	*/
	private $headers;
	
	/**
	* MIME-тип сообщения
	* @var string
	*/
	private $contentType;
	
	/**
	* MIME-субтип сообщения
	* @var string
	*/
	private $contentSubtype;
	
	/**
	* Параметры MIME-типа
	* @var array
	*/
	private $contentTypeParams;
	
	/**
	* Тело сообщения
	* @var string
	*/
	private $body;
	
	/**
	* Части письма, если Content-type: multipart/*
	* @var array
	*/
	private $multipart;
	
	/**
	* Конструктор парсера
	*/
	public function __construct()
	{
		$this->clear();
	}
	
	/**
	* Очистить структуры
	*/
	public function clear()
	{
		$this->headers = array ();
		$this->contentType = '';
		$this->contentSubtype = '';
		$this->contentTypeParams = array ();
		$this->body = '';
		$this->multipart = array ();
	}
	
	/**
	* Вернуть заголовок
	* @param string название заголовка
	* @param mixed значение по умолчанию, если заголовка нет
	* @retval mixed значение заголовка
	*/
	public function getHeader($name, $default = false)
	{
		$iname = strtolower($name);
		return isset($this->headers[$iname]) ? $this->headers[$iname] : $default;
	}
	
	/**
	* Вернуть заголовки в виде массива
	*/
	public function getHeaders()
	{
		return $this->headers;
	}
	
	/**
	* Установить заголовок
	* @param string название заголовока
	* @param string значение заголовка
	*/
	public function setHeader($name, $value)
	{
		$iname = strtolower($name);
		if ( $iname === 'content-type' ) $this->setContentType($value);
		else $this->headers[$iname] = trim($value);
	}
	
	/**
	* Вернуть транспортную кодировку
	* @param string значение по умолчанию, если не указано
	* @retval string траспортная кодировка
	*/
	public function getContentTransferEncoding($default = '7bit')
	{
		return $this->getHeader('content-transfer-encoding', $default);
	}
	
	/**
	* Вернуть тип контента
	*/
	public function getContentType($full = true)
	{
		return $full ? "{$this->contentType}/{$this->contentSubtype}" : $this->contentType;
	}
	
	public function getContentSubtype()
	{
		return $this->contentSubtype;
	}
	
	/**
	* Вернуть параметра типа
	* @param string название параметра
	* @param mixed значение параметра
	* @retval mixed значение параметра по умолчанию, если параметр не определен
	*/
	public function getContentTypeParam($name, $default = false)
	{
		$lname = strtolower($name);
		return isset($this->contentTypeParams[$lname]) ? $this->contentTypeParams[$lname] : $default;
	}
	
	/**
	* Установить значение параметра типа
	* @param string название параметра
	* @param string значение параметра
	*/
	public function setContentTypeParam($name, $value)
	{
		$this->contentTypeParams[strtolower($name)] = strval($value);
	}
	
	/**
	* Вернуть кодировку тела сообщения
	*/
	public function getContentCharset($default = false)
	{
		return $this->getContentTypeParam('charset', $default);
	}
	
	/**
	* Установить MIME-тип сообщения
	* @param string
	*/
	public function setContentType($value)
	{
		$this->headers['content-type'] = $value;
		$params = explode(';', $value);
		$type = explode('/', array_shift($params), 2);
		$this->contentType = strtolower(trim($type[0]));
		$this->contentSubtype = isset($type[1]) ? strtolower(trim($type[1])) : '';
		$this->contentTypeParams = array ();
		foreach($params as $param)
		{
			$pair = explode('=', $param, 2);
			if ( isset($pair[1]) )
			{
				$value = trim($pair[1]);
				if ( substr($value, 0, 1) == '"' && substr($value, -1) == '"' && strlen($value) > 1 )
				{
					$value = substr($value, 1, strlen($value) - 2);
				}
			}
			else $value = '';
			$this->contentTypeParams[ strtolower(trim($pair[0])) ] = $value;
		}
	}
	
	/**
	* Вернуть адрес для ответа
	*
	* Данный адрес вычисляется из анализа нескольких
	* заголовоков. Для получения точного значения заголовка
	* Reply-to используйте метод getHeader()
	*
	* @retval string адрес для ответа
	*/
	public function getReplyTo()
	{
		if ( $mail = $this->getHeader('Reply-to', false) ) return $mail;
		return $this->getHeader('From', false);
	}
	
	/**
	* Вернуть адрес отправителя
	*
	* Данный адрес вычисляется из анализа нескольких
	* заголовоков. Для получения точного значения заголовка
	* Sender используйте метод getHeader()
	*
	* @retval string адрес отправителя
	*/
	public function getSender()
	{
		if ( $mail = $this->getHeader('Sender', false) ) return $mail;
		return $this->getHeader('From', false);
	}
	
	/**
	* Вернуть тело сообщения
	*
	* Для текстов text/* возращает в кодировке UTF-8 вне зависимости
	* от того в каком виде была изначально
	*
	* @retval string тело сообщения
	*/
	public function getBody()
	{
		return $this->body;
	}
	
	/**
	* Установить тело сообщения
	* @param string тело сообщения
	*/
	public function setBody($value)
	{
		$this->body = $value;
	}
	
	/**
	* Проверить является multipart/*
	* @retval bool TRUE - это multipart-сообщение, FALSE - обычное сообщение
	*/
	public function isMultipart()
	{
		return $this->getContentType(false) === 'multipart';
	}
	
	/**
	* Вернуть число частей multipart/*
	* @retval integer число частей
	*/
	public function getMultipartCount()
	{
		return count($this->multipart);
	}
	
	/**
	* Вернуть часть multipart/*
	* @param integer номер части
	* @retval MimeMessage сообщение
	*/
	public function getMultipart($i)
	{
		return $this->multipart[$i];
	}
	
	/**
	* Вернуть часть multipart/* по MIME-типу
	* @param string MIME-тип искомого субсообщения
	* @param bool TRUE - искать рекусивно, FALSE искать только в непосредственных потомках
	* @retval MimeMessage сообщение
	*/
	public function getMultipartByType($contentType, $recursive = false)
	{
		$type = strtolower($contentType);
		foreach($this->multipart as $msg)
		{
			if ( $msg->getContentType() == $type ) return $msg;
			if ( $recursive && $msg->isMultipart() )
			{
				$tmp = $msg->getMultipartByType($contentType, true);
				if ( ! is_null($tmp) ) return $tmp;
			}
		}
		return null;
	}
	
	/**
	* Добавить часть
	* @param MimeMessage часть сообщения
	*/
	public function addMultipart(MimeMessage $message)
	{
		$this->multipart[] = $message;
	}
}

?>