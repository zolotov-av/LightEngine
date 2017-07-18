<?php

/*****************************************************************************

  Пример работы с модулем mod_des3028
    управление коммутатором DES-3028 через SNMP

 *****************************************************************************/

/**
* Подключаем двигало
*/
require dirname(__FILE__) . "/system.php";

$host = '127.0.0.1';

// удаление профиля
$engine->des3028->removeEthernetProfile($host, 14);

// создание профиля
$engine->des3028->setEthernetProfileState($host, 14, 4);
$engine->des3028->setEthernetProfileUseVlan($host, 14, false);
$engine->des3028->setEthernetProfileMacAddrMaskState($host, 14, 3);
$engine->des3028->setEthernetProfileSrcMacMask($host, 14, 'FF-FF-FF-FF-FF-FF');
//$engine->des3028->setEthernetProfileDstMacMask($host, 14, '00-00-00-00-00-00');
$engine->des3028->setEthernetProfileUse8021p($host, 14, false);
$engine->des3028->setEthernetProfileUseEthernetType($host, 14, false);
