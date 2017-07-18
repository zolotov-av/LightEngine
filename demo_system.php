<?php

/*****************************************************************************

  Пример работы с модулем mod_system (простая конфигурация в ini-файле)

  Строковые константы для модуля локализации хранятся в каталоге DIR_LANGS

 *****************************************************************************/

/**
* Подключаем двигало
*/
require dirname(__FILE__) . "/system.php";

echo "root: " . DIR_ROOT . "\n";
echo $engine->system->read('username.minlen', 3) . "\n";
echo $engine->system->read('username.maxlen', 10) . "\n";
$engine->system->write('test.foo', 50);
$engine->system->commit();
