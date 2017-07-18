<?php

/**
* Виртуальный модуль драйвера БД
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2009
*/
class mod_db extends LightModule
{
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
		$db_config = require DB_CONFIG_PATH;
		$db = $engine->lookup($db_config['driver']);
		$db->connect($db_config);
		return $db;
	}
}
