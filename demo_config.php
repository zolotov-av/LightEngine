<?php

/*****************************************************************************

  Пример работы с модулем mod_config (модуль глобальной конфигурации)

  Модуль config хранит данные в базе данных в таблице config. Для запуска
  примера надо чтобы была создана и настроена база данных и в ней присутсвовала
  таблица config, см. определение таблиц в demo_db.sql

  Пример запуска:

  > php demo_config.php
  site url: http://example.com/
  site prefix: /

 *****************************************************************************/

/**
* Подключаем двигало
*/
require dirname(__FILE__) . "/system.php";

try
{
	$site_url = $engine->config->read('site_url', 'http://example.com/');
	$site_prefix = $engine->config->read('site_prefix', '/');
	
	echo "site url: $site_url\n";
	echo "site prefix: $site_prefix\n";
}
catch (db_exception $e)
{
	echo "DB error: " . $e->getDriverMessage() . "\n";
	echo "Query: " . $e->getQuery() . "\n";
}
