<?php

/*****************************************************************************

  Пример работы с модулем mod_post (обработчик форм)

  В LightEngine представлено два модуля обработчика форм mod_post и mod_forms.
  В данном примере представлен обработчик форм на базе mod_post.

  mod_post берет описания форм из файлов *.frm в каталоге DIR_FORMS, которые
  представляют собой ini-файлы

  Пример запуска:

  > php demo_lang.php
  page_not_found:  Страница не найдена
  read_file_fault: Ошибка чтения файла foo.txt

 *****************************************************************************/

/**
* Подключаем двигало
*/
require dirname(__FILE__) . "/system.php";

try
{
	$form = $engine->post->parse('user/register');
	$tag = $form->makeFormTag();
	print_r($tag);
}
catch (Exception $e)
{
	$class = get_class($e);
	$message = $e->getMessage();
	echo "Error ($class): $message\n";
}
