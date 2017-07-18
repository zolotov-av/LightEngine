<?php

/*****************************************************************************

  Шаблон конфигурационного файла для deb-пакета

 *****************************************************************************/

/**
* Корень сайта
*/
define('DIR_ROOT', '/var/lib/lightengine');

/**
* Пути поиска классов
*
* Пути могут быть указаны как относительно DIR_ROOT, так и полные. Порядок
* имеет значение, модули ищутся слева направо.
*/
define('DIR_PATH', 'modules:engine/modules');

/**
* Движок
*/
require_once DIR_ROOT . "/engine/engine.php";

/**
* Каталог со скинами
*/
define('DIR_THEMES', makepath(DIR_ROOT, 'themes'));

/**
* Каталог для кеша
*/
define('DIR_CACHE', makepath(DIR_ROOT, 'cache'));

/**
* Язык по умолчанию
*/
define('LANG_DEFAULT', 'russian');

/**
* Каталог с файлами локализации
*/
define('DIR_LANGS', makepath(DIR_ROOT, 'lang'));

/**
* Каталог для описаний форм
*/
define('DIR_FORMS', makepath(DIR_ROOT, 'forms'));

/**
* Путь к конфигурационному файлу модуля mod_db
*/
define('DB_CONFIG_PATH', '/etc/lightengine/db.config.php');

/**
* Загрузчик классов
*/
function __autoload($class)
{
	LightEngine::loadClass($class);
}

/**
* Глобальная переменная хранящая ссылку на объект движка
*/
$engine = new LightEngine();
