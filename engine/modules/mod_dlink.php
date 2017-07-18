<?php

/**
* Модуль для работы с коммутаторами D-Link через SNMP
*
* (с) Золотов Алексей <zolotov-alex@shamangrad.net>, 2010
*/
class mod_dlink extends mod_snmp
{
	protected $read_community;
	protected $write_community;
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	*/
	public function __construct(LightEngine $engine)
	{
		parent::__construct($engine);
		$this->read_community = $engine->config->read('dlink-read-community', 'public');
		$this->write_community = $engine->config->read('dlink-write-community', 'private');
	}
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_dlink($engine);
	}
	
	/**
	* Найти порт на котором сидит MAC-адрес
	* @param string IP-адрес коммутатора
	* @param string MAC-адрес (XX-XX-XX-XX-XX-XX или XX:XX:XX:XX:XX:XX)
	* @param integer номер порта или -1 если не найден
	*/
	public function findPortByMAC($device, $mac)
	{
		$bytes = preg_split('/:|-/', $mac);
		$mac_oid = array ();
		foreach($bytes as $byte)
		{
			$mac_oid[] = hexdec($byte);
		}
		return $this->read($device, $this->read_community, 'SNMPv2-SMI::mib-2.17.4.3.1.2.' . implode('.', $mac_oid));
	}
}

?>