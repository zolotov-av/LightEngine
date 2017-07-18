<?php

/**
* Модуль для работы с коммутаторами D-Link DES-3028 через SNMP
*
* (с) Золотов Алексей <zolotov-alex@shamangrad.net>, 2010
*/
class mod_des3028 extends mod_dlink
{
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_des3028($engine);
	}
	
	public function getEthernetProfileUseVlan($device, $profileId)
	{
		return $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.2.1.1.2.' . intval($profileId)) == 1;
	}
	
	public function setEthernetProfileUseVlan($device, $profileId, $value)
	{
		return $this->setInteger($device, $this->write_community, '1.3.6.1.4.1.171.12.9.2.1.1.2.' . intval($profileId), $value ? 1 : 2);
	}
	
	public function getEthernetProfileMacAddrMaskState($device, $profileId)
	{
		return $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.2.1.1.3.' . intval($profileId));
	}
	
	public function setEthernetProfileMacAddrMaskState($device, $profileId, $value)
	{
		return $this->setInteger($device, $this->write_community, '1.3.6.1.4.1.171.12.9.2.1.1.3.' . intval($profileId), $value);
	}
	
	public function getEthernetProfileSrcMacMask($device, $profileId)
	{
		return str_replace(' ', ':', $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.2.1.1.4.' . intval($profileId)));
	}
	
	public function setEthernetProfileSrcMacMask($device, $profileId, $mac)
	{
		$value = strtoupper(preg_replace('/:|-/', ' ', $mac));
		return $this->setOctetString($device, $this->write_community, '1.3.6.1.4.1.171.12.9.2.1.1.4.' . intval($profileId), $value);
	}
	
	public function getEthernetProfileDstMacMask($device, $profileId)
	{
		return str_replace(' ', ':', $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.2.1.1.5.' . intval($profileId)));
	}
	
	public function setEthernetProfileDstMacMask($device, $profileId, $mac)
	{
		$value = strtoupper(preg_replace('/:|-/', ' ', $mac));
		return $this->setOctetString($device, $this->write_community, '1.3.6.1.4.1.171.12.9.2.1.1.5.' . intval($profileId), $value);
	}
	
	public function getEthernetProfileUse8021p($device, $profileId)
	{
		return $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.2.1.1.6.' . intval($profileId)) == 1;
	}
	
	public function setEthernetProfileUse8021p($device, $profileId, $value)
	{
		return $this->setInteger($device, $this->write_community, '1.3.6.1.4.1.171.12.9.2.1.1.6.' . intval($profileId), $value ? 1 : 2);
	}
	
	public function getEthernetProfileUseEthernetType($device, $profileId)
	{
		return $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.2.1.1.7.' . intval($profileId)) == 1;
	}
	
	public function setEthernetProfileUseEthernetType($device, $profileId, $value)
	{
		return $this->setInteger($device, $this->write_community, '1.3.6.1.4.1.171.12.9.2.1.1.7.' . intval($profileId), $value ? 1 : 2);
	}
	
	/**
	* Вернуть статус Ethernet-профиля
	* @param string IP-адрес коммутатора
	* @param integer ID профиля
	* @retval integer статус профиля
	*/
	public function getEthernetProfileState($device, $profileId)
	{
		return $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.2.1.1.8.' . intval($profileId));
	}
	
	/**
	* Вернуть список Ethernet-профилей
	* @param string IP-адрес коммутатора
	* @retval array список ID профилей
	*/
	public function listEthernetProfiles($device)
	{
		$items = $this->walkoid($device, $this->read_community, '1.3.6.1.4.1.171.12.9.2.1.1.8');
		$result = array ();
		foreach($items as $oid => $value)
		{
			if ( preg_match('/\.(\d+)$/', $oid, $match) )
			{
				$result[] = intval($match[1]);
			}
		}
		return $result;
	}
	
	/**
	* Установить статус Ethernet-профиля
	* @param string IP-адрес коммутатора
	* @param integer ID профиля
	* @param integer статус профиля
	* @retval bool TRUE - успешно, FALSE - ошибка
	*/
	public function setEthernetProfileState($device, $profileId, $state)
	{
		return $this->setInteger($device, $this->write_community, '1.3.6.1.4.1.171.12.9.2.1.1.8.' . intval($profileId), $state);
	}
	
	/**
	* Удалить Ethernet-профиль
	* @param string IP-адрес коммутатора
	* @param integer ID профиля
	* @param integer статус профиля
	*/
	public function removeEthernetProfile($device, $profileId)
	{
		return $this->setEthernetProfileState($device, $profileId, 6);
	}
	
	/**
	* Вернуть список правил в Ethernet-профиле
	* @param string IP-адрес коммутатора
	* @param integer ID профиля
	* @retval array список ID правил
	*/
	public function listEthernetProfileRules($device, $profileId)
	{
		$items = $this->walkoid($device, $this->read_community, '1.3.6.1.4.1.171.12.9.3.1.1.8.'.intval($profileId));
		$result = array ();
		foreach($items as $oid => $value)
		{
			if ( preg_match('/\.(\d+)$/', $oid, $match) )
			{
				$result[] = intval($match[1]);
			}
		}
		return $result;
	}
	
	public function getEthernetRuleVlan($device, $profileId, $ruleId)
	{
		return $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.3.1.1.3.' . intval($profileId) . '.' . intval($ruleId));
	}
	
	public function setEthernetRuleVlan($device, $profileId, $ruleId)
	{
		return $this->setString($device, $this->write_community, '1.3.6.1.4.1.171.12.9.3.1.1.3.' . intval($profileId) . '.' . intval($ruleId), $state);
	}
	
	public function getEthernetRuleSrcMac($device, $profileId, $ruleId)
	{
		return str_replace(' ', ':', $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.3.1.1.4.' . intval($profileId) . '.' . intval($ruleId)));
	}
	
	public function setEthernetRuleSrcMac($device, $profileId, $ruleId, $mac)
	{
		$value = strtoupper(preg_replace('/:|-/', ' ', $mac));
		return $this->setOctetString($device, $this->write_community, '1.3.6.1.4.1.171.12.9.3.1.1.4.' . intval($profileId) . '.' . intval($ruleId), $value);
	}
	
	public function getEthernetRuleDstMac($device, $profileId, $ruleId)
	{
		return str_replace(' ', ':', $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.3.1.1.5.' . intval($profileId) . '.' . intval($ruleId)));
	}
	
	public function setEthernetRuleDstMac($device, $profileId, $ruleId, $mac)
	{
		$value = strtoupper(preg_replace('/:|-/', ' ', $mac));
		return $this->setOctetString($device, $this->write_community, '1.3.6.1.4.1.171.12.9.3.1.1.5.' . intval($profileId) . '.' . intval($ruleId), $value);
	}
	
	public function getEthernetRule8021p($device, $profileId, $ruleId)
	{
		return $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.3.1.1.6.' . intval($profileId) . '.' . intval($ruleId));
	}
	
	public function setEthernetRule8021p($device, $profileId, $ruleId, $value)
	{
		return $this->setInteger($device, $this->write_community, '1.3.6.1.4.1.171.12.9.3.1.1.6.' . intval($profileId) . '.' . intval($ruleId), $value);
	}
	
	public function getEthernetRuleEthernetType($device, $profileId, $ruleId)
	{
		return hexdec(str_replace(' ', '', $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.3.1.1.7.' . intval($profileId) . '.' . intval($ruleId))));
	}
	
	public function setEthernetRuleEthernetType($device, $profileId, $ruleId, $value)
	{
		$x = sprintf("%04x", intval($value));
		return $this->setOctetString($device, $this->write_community, '1.3.6.1.4.1.171.12.9.3.1.1.7.' . intval($profileId) . '.' . intval($ruleId), $x);
	}
	
	public function getEthernetRulePermit($device, $profileId, $ruleId)
	{
		return $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.3.1.1.8.' . intval($profileId) . '.' . intval($ruleId)) == 1;
	}
	
	public function setEthernetRulePermit($device, $profileId, $ruleId, $value)
	{
		return $this->setInteger($device, $this->write_community, '1.3.6.1.4.1.171.12.9.3.1.1.8.' . intval($profileId) . '.' . intval($ruleId), $value ? 1 : 2);
	}
	
	public function getEthernetRulePriority($device, $profileId, $ruleId)
	{
		return $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.3.1.1.9.' . intval($profileId) . '.' . intval($ruleId));
	}
	
	public function setEthernetRulePriority($device, $profileId, $ruleId, $value)
	{
		return $this->setInteger($device, $this->write_community, '1.3.6.1.4.1.171.12.9.3.1.1.9.' . intval($profileId) . '.' . intval($ruleId), $value);
	}
	
	//---------
	// 10, 13, 16
	//---------
	
	/**
	* TODO parse
	*/
	public function getEthernetRulePorts($device, $profileId, $ruleId)
	{
		return $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.3.1.1.14.' . intval($profileId) . '.' . intval($ruleId));
	}
	
	/**
	* TODO parse
	*/
	public function setEthernetRulePorts($device, $profileId, $ruleId, $value)
	{
		return $this->setOctetString($device, $this->write_community, '1.3.6.1.4.1.171.12.9.3.1.1.14.' . intval($profileId) . '.' . intval($ruleId), $value);
	}
	
	/**
	* Вернуть статус правила в Ethernet-профиле
	* @param string IP-адрес коммутатора
	* @param integer ID профиля
	* @param integer ID правила
	* @retval integer статус правила
	*/
	public function getEthernetRuleState($device, $profileId, $ruleId)
	{
		return $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.3.1.1.15.' . intval($profileId) . '.' . intval($ruleId));
	}
	
	/**
	* Вернуть статус правила в Ethernet-профиле
	* @param string IP-адрес коммутатора
	* @param integer ID профиля
	* @param integer ID правила
	* @param integer статус правила
	* @retval bool TRUE - успешно, FALSE - ошибка
	*/
	public function setEthernetRuleState($device, $profileId, $ruleId, $state)
	{
		return $this->setInteger($device, $this->write_community, '1.3.6.1.4.1.171.12.9.3.1.1.15.' . intval($profileId) . '.' . intval($ruleId), $state);
	}
	
	public function getEthernetRuleRxRateLimit($device, $profileId, $ruleId)
	{
		return $this->read($device, $this->read_community, '1.3.6.1.4.1.171.12.9.3.1.1.17.' . intval($profileId) . '.' . intval($ruleId));
	}
	
	public function setEthernetRuleRxRateLimit($device, $profileId, $ruleId, $value)
	{
		return $this->setInteger($device, $this->write_community, '1.3.6.1.4.1.171.12.9.3.1.1.17.' . intval($profileId) . '.' . intval($ruleId), $value);
	}
	
	/**
	* Удалить статус правила в Ethernet-профиле
	* @param string IP-адрес коммутатора
	* @param integer ID профиля
	* @param integer ID правила
	* @param integer статус правила
	* @retval bool TRUE - успешно, FALSE - ошибка
	*/
	public function removeEthernetRule($device, $profileId, $ruleId)
	{
		return $this->setEthernetRuleState($device, $profileId, $ruleId, 6);
	}
	
	/**
	* Вернуть список всех правил всех Ethernet-профилей
	* @param string IP-адрес коммутатора
	* @retval array список ID профилей, в каждом профиле правило
	*/
	public function listEthernetRules($device)
	{
		$items = $this->walkoid($device, $this->read_community, '1.3.6.1.4.1.171.12.9.3.1.1.15');
		$result = array ();
		foreach($items as $oid => $value)
		{
			if ( preg_match('/\.(\d+)\.(\d+)$/', $oid, $match) )
			{
				$result[ intval($match[1]) ][ intval($match[2]) ] = intval($match[2]);
			}
		}
		return $result;
	}
}

?>