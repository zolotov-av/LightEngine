<?php

/**
* Парсер MIME
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2009
*
* @package mod_mime
*/
class mod_mime extends LightModule
{
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_mime($engine);
	}
	
	/**
	* Unfolding
	* 
	* Unfolding is accomplished by simply removing any CRLF
	* that is immediately followed by WSP.
	* 
	* @see RFC 2822 (2.2.3. Long Header Fields)
	*/
	protected static function unfold($text)
	{
		return preg_replace("/\r?\n([ \t])/", "", $text);
	}
	
	public static function qp_decode($text)
	{
		return preg_replace("/=([0-9A-Fa-f]{2,2})/e", "chr(0x\\1)", preg_replace("/=\r?\n/", "", $text));
	}
	
	protected static function decodeHeaderCallback($match)
	{
		//print_r($match);
		switch ( strtoupper($match[2]) )
		{
		case "B": return iconv($match[1], 'UTF-8', base64_decode($match[3]));
		case "Q": return iconv($match[1], 'UTF-8', self::qp_decode(str_replace('_', ' ', $match[3])));
		default: $match[0];
		}
	}
	
	protected function decodeHeader($text)
	{
		//echo "\nsrc: $text\n";
		return preg_replace_callback("/=\?([A-Za-z_0-9\-]+)\?([BbQq])\?([^\s]+?)\?=/", array($this, 'decodeHeaderCallback'), $text);
	}
	
	protected static function contentDecode($body, $contentEncoding)
	{
		switch ( strtolower($contentEncoding) )
		{
		case 'base64': return base64_decode($body);
		case 'quoted-printable': return self::qp_decode($body);
		default: return $body;
		}
	}
	
	protected static function parseBody($body, $mail)
	{
		$content = self::contentDecode($body, $mail->getContentTransferEncoding());
		if ( $mail->getContentType(false) == 'text' )
		{
			$charset = strtoupper($mail->getContentCharset('UTF-8'));
			if ( $charset !== 'UTF-8' ) return iconv($charset, 'UTF-8', $content);
		}
		return $content;
	}
	
	/**
	* Парсинг multipart/*
	* @param string тело
	* @param MimeMessage родительское сообщение
	*/
	public function parseMultipart($body, MimeMessage $parent)
	{
		if ( $boundary = $parent->getContentTypeParam('boundary', false) )
		{
			$sep = "/\r?\n" . preg_quote("--$boundary", "/") . "\r?\n/";
			$end = "/\r?\n" . preg_quote("--$boundary--", "/") . "\r?\n/";
			list($body) = preg_split($end, "\r\n$body\r\n", 2);
			$parts = preg_split($sep, $body);
			array_shift($parts);
			foreach($parts as $part)
			{
				$parent->addMultipart($this->parse($part));
			}
		}
	}
	
	/**
	* Парсинг сообщения
	* @param string текст сообщения
	* @retval bool TRUE - успешно, FALSE - произошла ошибка
	*/
	public function parse($message)
	{
		$mail = new MimeMessage();
		
		$pair = preg_split("/(\r?\n){2,2}/", $message, 2);
		$headers = explode("\n", $this->unfold($pair[0]));
		$body = isset($pair[1]) ? $pair[1] : "";
		
		foreach($headers as $header)
		{
			$pair = explode(':', $header, 2);
			$name = strtolower(trim($pair[0]));
			$value = isset($pair[1]) ? $pair[1] : '';
			$mail->setHeader($name, $this->decodeHeader($value));
		}
		
		if ( $mail->getContentType(false) == 'multipart' )
		{
			$mail->setBody($body);
			$this->parseMultipart($body, $mail);
		}
		else
		{
			$mail->setBody($this->parseBody($body, $mail));
		}
		return $mail;
	}
	
	/**
	* Парсинг мыла в виде "Имя <e-mail>"
	*/
	public function parseMail($mail)
	{
		if ( preg_match('/^([^<]*)<([^>]*)>/', $mail, $match) )
		{
			return array (
			'name' => trim($match[1]),
			'mail' => trim($match[2])
			);
		}
		else
		{
			return array (
			'name' => '',
			'mail' => trim($mail)
			);
		}
	}
}

?>