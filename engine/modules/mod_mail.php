<?php

/**
* Модуль MTA
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2007-2009
*
* @package mod_mail
*/
class mod_mail extends LightModule
{
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_mail($engine);
	}
	
	/**
	* Закодировать строку
	* @param $string исходная 8 битная строка
	* @return закодированя строка
	*/
	function encode($string, $charset = 'utf-8')
	{
		return "=?$charset?B?" . base64_encode($string) . "?=\r\n";
	}
	
	/**
	* Закодировать адрес получатея
	* @param $mail адрес получателя
	* @param $name имя получателя
	* @param $charset кодировка, по умолчанию UTF-8
	* @return закодированная строка
	*/
	public function encodeMail($mail, $name = '', $charset = 'utf-8')
	{
		if ( $name )
		{
			return $this->encode($name) . " <$mail>";
		}
		else
		{
			return $mail;
		}
	}
	
	/**
	* Отправить письмо
	* @param string имя/адрес получателя
	* @param string имя/адрес отправителя
	* @param string имя/адрес для ответа
	* @param string тема письма
	* @param string текст сообщения
	* @param string тип контента
	* @return true - письмо отравлено
	*/
	public function sendMail($mailto, $mailfrom, $subject, $body, $contentType)
	{
		$headers = array
		(
			"MIME-Version: 1.0",
			"From: $mailfrom",
			"X-Powered-By: LightEngine/mod_mail",
			"Content-Type: $contentType",
			'Content-Transfer-Encoding: base64'
		);
		
		return mail
		(
			$mailto,
			$this->encode($subject),
			chunk_split(base64_encode($body)),
			implode("\n", $headers) . "\n"
		);
	}
}

?>