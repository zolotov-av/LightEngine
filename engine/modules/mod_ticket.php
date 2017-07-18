<?php

/**
* WSCore 2.0: Web Service Core framework
*
* Модуль TicketID
*
* (c) Zolotov Alex, 2007-2008, 2010
*     zolotov-alex@shamangrad.net
*     http://shamangrad.net/
*/
class mod_ticket extends LightModule
{
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_ticket($engine);
	}
	
	/**
	* Вычисление HMAC MD5
	* @param $message подписываемое сообщение
	* @param $key секретный ключ
	* @return хеш в виде строки (32 символа)
	*/
	protected function hmac_md5($message, $key)
	{
		if ( strlen($key) > 64 ) $key = md5($key, true);
		$kpad = str_pad($key, 64, "\0");
		$ipad = str_pad('', 64, "\x36");
		$opad = str_pad('', 64, "\x5c");
		return md5( ($kpad ^ $opad) . md5(($kpad ^ $ipad) . $message, true) );
	}
	
	/**
	* Подписать новый билет
	* @param $expire время действия билета (в секундах)
	* @param $message подписываемое секретное сообшение
	* @return билет в виде строки до 80-символов
	*/
	public function sign($expire, $message = '')
	{
		$ticketID = sprintf("%06X%06X", rand(0, 0xFFFFFF), rand(0, 0xFFFFFF));
		$time = time();
		$key = $this->config->read('TicketID:Secret');
		$part = "$ticketID:$time:$expire";
		$hash = $this->hmac_md5("$part:$message", $key);
		return "$part:$hash";
	}

	/**
	* Проверить билет
	* @param $ticket билет
	* @param $message секретное сообщение
	* @return true если билет подлинный и актуальный
	* @note не проверяет был ли билет использован, см. ticket_is_broken()
	*/
	public function verify($ticket, $message = '')
	{
		list($ticketID, $time, $expire, $hash) = explode(':', $ticket, 4);
		$now = time();
		$key = $this->config->read('TicketID:Secret');
		return ($now >= intval($time)) && ($now < intval($time + $expire)) &&
			($hash === $this->hmac_md5("$ticketID:$time:$expire:$message", $key));
	}

	/**
	* Пометить билет использованным
	* @param $ticket билет
	* @param $savefor время хранения билета (в секундах)
	*/
	public function breakTicket($ticket, $savefor = false)
	{
		if ( $savefor === false )
		{
			list($ticketID, $time, $expire, $hash) = explode(':', $ticket);
			$savefor = $expire;
		}
		$this->db->insert('tickets', array (
		'ticket_id' => $this->db->quote($ticket),
		'ticket_expire' => intval(time() + $savefor)
		));
	}

	/**
	* Проверить использован ли билет
	* @param $ticket билет
	* @return true если билет был использован
	* @note данная функция не проверяет правильность билета
	*/
	public function isBroken($ticket)
	{
		return $this->db->selectOne('tickets', 'ticket_id, ticket_expire',
			'ticket_id = ' . $this->db->quote($ticket));
	}

	/**
	* Удалить устаревшие билеты
	*
	* Удаляет использованные билеты срок хранения которых уже истёк
	*/
	public function cleanup()
	{
		$this->db->delete('tickets', 'ticket_expire < ' . time());
	}
}

?>