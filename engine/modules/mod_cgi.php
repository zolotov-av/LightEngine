<?php

/**
* Вспомогательные функции для CGI-приложений
*
* Пока в основном работа с заголовками: кеширование, куки и т.п.
*
* (с) Золотов Алексей <zolotov-alex@shamangrad.net>, 2009
*/
class mod_cgi extends LightModule
{
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	*/
	public function __construct(LightEngine $engine, $table = 'config')
	{
		parent::__construct($engine);
		$this->engine->define('MOD_CGI_COOKIE_PREFIX', 'le_');
		$this->engine->define('MOD_CGI_COOKIE_PATH', '/');
	}
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_cgi($engine);
	}
	
	/**
	* Установить куки
	* @param string название куки
	* @param string значение куки
	* @param int время хранения куки
	*/
	public function setCookie($name, $value, $savefor)
	{
		SetCookie(MOD_CGI_COOKIE_PREFIX . $name, $value, time() + $savefor, MOD_CGI_COOKIE_PATH);
	}
	
	/**
	* Удалить куку
	* @param string название куки
	*/
	public function removeCookie($name)
	{
		SetCookie(MOD_CGI_COOKIE_PREFIX . $name, '', 0, MOD_CGI_COOKIE_PATH);
	}
	
	/**
	* Прочитать куки
	* @param string название куки
	* @param mixed значение по умолчанию, если кукис не определен
	* @retval mixed значение кукиса
	*/
	public function getCookie($name, $default = false)
	{
		if ( isset($_COOKIE[MOD_CGI_COOKIE_PREFIX . $name]) )
		{
			return $_COOKIE[MOD_CGI_COOKIE_PREFIX . $name];
		}
		return $default;
	}
	
	/**
	* Оправить заголовки запрещающие кеширование
	*/
	public function disableCache()
	{
		header("Pragma: no-cache");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Cache-Control: no-cache, must-revalidate");
	}
	
	/**
	* Включить кеширование только у клиента
	* @param int время устаревания контента
	*/
	public function enablePrivateCache($expires)
	{
		header('Expires: ' . gmdate('r', $expires));
		header('Cache-Control: private, proxy-revalidate');
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	}
	
	/**
	* Включить "публичное" кеширование
	* Контент может кешироваться как на стороне пользователя баузером, так на прокси
	* @param int время устаревания контента
	*/
	public function enablePublicCache($expires)
	{
		header('Expires: ' . gmdate('r', time() + $savefor));
		header('Cache-Control: public');
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	}
	
	/**
	* Вернуть полный URL запрошенной страницы
	*/
	public static function requestURL()
	{
		if ( empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) === 'off' )
		{
			$scheme = 'http';
			$port = $_SERVER['SERVER_PORT'] == 80 ? '' : ":$_SERVER[SERVER_PORT]";
		}
		else
		{
			$scheme = 'https';
			$port = $_SERVER['SERVER_PORT'] == 443 ? '' : ":$_SERVER[SERVER_PORT]";
		}
		return "$scheme://$_SERVER[SERVER_NAME]$port$_SERVER[REQUEST_URI]";
	}
}

?>