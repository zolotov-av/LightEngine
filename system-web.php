<?php

/*****************************************************************************

  Пример конфигурационного файла system.php для веб-приложения

  Здесь могут быть не только определения констант, но также инициализирующий
  код, объявление глобальных функций или подключение каких-либо дополнительных
  скриптов/библиотек. В проекте возможно несколько разных файлов system.php,
  например один для панели администрирования, второй для личного кабинета
  пользователя, третий для консольных утилит для администрирования. Я в своем
  проекте (lanbill) создавал файл core.php который хранил общие объявления и
  подключал его из нескольких файлов system.php.

 *****************************************************************************/

/**
* Корень сайта
*/
define('DIR_ROOT', dirname(__FILE__));

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
define('DB_CONFIG_PATH', makepath(DIR_ROOT, 'config', 'db-example-mysql.php'));

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

/*****************************************************************************/
// добавьте здесь инициализацию модулей

$engine->tpl->set_tag('LAYOUT', 'layout/simple');
$engine->tpl->set_tag('LBR', '{');
$engine->tpl->set_tag('RBR', '}');
$engine->tpl->open('INFO');
	$engine->tpl->set_tag('PREFIX', $engine->config->read('site_prefix', '/'));
	$engine->tpl->set_tag('SKINDIR', $engine->config->read('site_prefix', '/') . 'themes/default');
$engine->tpl->close();
$engine->session->init();

header('Content-type: text/html; charset=utf-8');
