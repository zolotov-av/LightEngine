<?php

/**
* Модуль для работы с SNMP
*
* (c) Zolotov Alex, 2010
*     zolotov-alex@shamangrad.net
*     http://shamangrad.net/
*/
class mod_snmp extends LightModule
{
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_snmp($engine);
	}
	
	/**
	* Парсинг возврата snmpget
	*/
	public function parse($result)
	{
		if ( $result === false ) return false;
		if ( $result === '""' ) return "";
		if ( preg_match('/^([A-Za-z\-0-9]+):(.*)$/s', $result, $match) )
		{
			switch ( strtoupper($match[1]) )
			{
			case 'INTEGER':
			case 'COUNTER32':
				return intval(trim($match[2]));
			case 'HEX-STRING':
				return trim($match[2]);
			case 'STRING':
				return substr(trim($match[2]), 1, -1);
			}
		}
		var_dump($result);
		throw new exception("cannot parse snmpget's result: $result");
	}
	
	/**
	* Прочитать SNMP-параметр
	* @param string IP-адрес устройства
	* @param string "пароль"
	* @param string OID параметра
	* @retval mixed значение параметра
	*/
	public function read($device, $community, $oid)
	{
		return $this->parse(@snmpget($device, $community, $oid));
	}
	
	/**
	* Поиск SNMP-параметров
	* @param string IP-адрес устройства
	* @param string "пароль"
	* @param string префикс OID
	* @retval mixed значение параметра
	*/
	public function walkoid($device, $community, $oid)
	{
		return snmpwalkoid($device, $community, $oid);
	}
	
	/**
	* Записать SNMP-параметр
	* @param string IP-адрес устройства
	* @param string "пароль"
	* @param string OID параметра
	* @param integer значение параметра
	* @retval bool TRUE - успешно, FALSE - ошибка
	*/
	public function setInteger($device, $community, $oid, $value)
	{
		return snmpset($device, $community, $oid, 'i', intval($value));
	}
	
	/**
	* Записать SNMP-параметр
	* @param string IP-адрес устройства
	* @param string "пароль"
	* @param string OID параметра
	* @param string значение параметра (в шестнадцатеричном виде)
	* @retval bool TRUE - успешно, FALSE - ошибка
	*/
	public function setOctetString($device, $community, $oid, $value)
	{
		return snmpset($device, $community, $oid, 'x', $value);
	}
	
}

?>