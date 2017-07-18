<?php

/*****************************************************************************

  Пример работы с модулем mod_docs

  Экспериментальный модуль, который так и не получил развитие. Позволяет
  хранить и извлекать иерархически организованные документы.

 *****************************************************************************/

/**
* Подключаем двигало
*/
require dirname(__FILE__) . "/system.php";

try
{
	if ( $doc = $engine->doc->openDocument('/hello') )
	{
		echo "title: " . $doc->getDocumentTitle() . "\n";
		echo $doc->getDocumentContent() . "\n";
		$thumb = $doc->readParam('thumb', 'nothumb.jpg');
		echo "thumb: " . $doc->getDocumentFile($thumb) . "\n";
	}
	else
	{
		echo "not found\n";
	}
}
catch(db_exception $e)
{
	echo "Database error: " . $e->getMessage() . "\n";
	echo "Driver message: " . $e->getDriverMessage() . "\n";
	echo "Query:\n";
	echo $e->getQuery();
	echo "\n\n";
}
catch (Exception $e)
{
	$class = get_class($e);
	$message = $e->getMessage();
	echo "Error ($class): $message\n";
}

echo "\n\n";
echo "SQL query count: " . $engine->db->getQueryCount() . "\n";
echo "SQL time: " . $engine->db->getWorkTime() . "\n";
