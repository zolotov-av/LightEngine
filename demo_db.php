<?php

/*****************************************************************************

  Пример работы с модулем mod_db (база данных SQL)

  Пример запуска:

  > php demo_db.php
  site_prefix: /

 *****************************************************************************/

/**
* Подключаем двигало
*/
require dirname(__FILE__) . "/system.php";

try
{
	$rows = $engine->db->selectAll('config', '*', '', 'config_name ASC');
	foreach($rows as $row)
	{
		echo "$row[config_name]: $row[config_value]\n";
	}
	echo "\n";
}
catch(db_exception $e)
{
	echo "Database error: " . $e->getMessage() . "\n";
	echo "Query:\n";
	echo $e->getQuery();
	echo "\n\n";
}
