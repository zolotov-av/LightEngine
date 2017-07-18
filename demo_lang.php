<?php

/*****************************************************************************

  Пример работы с модулем mod_lang (модуль локализации)

  Строковые константы для модуля локализации хранятся в каталоге DIR_LANGS

  Пример запуска:

  > php demo_lang.php
  page_not_found:  Страница не найдена
  read_file_fault: Ошибка чтения файла foo.txt

 *****************************************************************************/

/**
* Подключаем двигало
*/
require dirname(__FILE__) . "/system.php";

echo "page_not_found:  " . $engine->lang->format('std:page_not_found'). "\n";
echo "read_file_fault: " . $engine->lang->format('std:read_file_fault', "foo.txt"). "\n";
