<?php

/**
* HTML-помощник
*
* Вспомогательный компонент для формирования HTML-страниц
* На данный момент основная функция этого компонента сбор информации для
* формирования заголовка HTML станицы, в частности содержимого тега HTML/HEAD
*
* (c) Zolotov Alex, 2008-2010
*     zolotov-alex@shamangrad.net
*     http://shamangrad.net/
*/
class mod_html extends LightModule
{
	/**
	* Заголовок страницы
	*/
	public $title = '';
	
	/**
	* Список дополнительных CSS
	*/
	protected $css = array ();
	
	/**
	* Список дополнительных JavaScript
	*/
	protected $script = array ();
	
	/**
	* Список дополнительных meta-тегов
	*/
	protected $meta = array ();
	
	/**
	* Список дополнительных meta-тегов (http-equiv)
	*/
	protected $http_equiv = array ();
	
	/**
	* Список дополнительных тегов link
	*/
	protected $link = array ();
	
	/**
	* Конструктор HTML-помошника
	*/
	public function __construct(LightEngine $engine)
	{
		// По умолчанию тип контента text/html и кодировка UTF-8
		$this->httpEquiv('Content-Type', 'text/html; charset=UTF-8');
	}
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_html($engine);
	}
	
	/**
	* Подключить дополнительный CSS-файл
	* @param string URL к CSS-файлу
	* @param mixed $media
	*/
	public function css($URL, $media = false)
	{
		$this->css[ $URL ] = array('URL' => $URL, 'media' => $media);
	}
	
	/**
	* Подключить дополнительный скрипт
	* @param string URL к скрипту
	* @param string тип скрита (язык), по умолчанию text/javascript
	*/
	public function script($URL, $type = 'text/javascript')
	{
		$this->script[ $URL ] = array('URL' => $URL, 'type' => $type);
	}
	
	/**
	* Добавить дополнительный meta-тег
	* @param string имя параметра
	* @param string значение параметра
	* @note если meta-тег с указаным именем уже был добавлен,
	*   то он будет заменён на новый
	*/
	public function meta($name, $content)
	{
		$this->meta[ strtolower($name) ] = array('name' => $name, 'value' => $content);
	}
	
	/**
	* Добавить http-equiv мета-тег
	* @param string имя заголовока
	* @param string значение заголовка
	*/
	public function httpEquiv($name, $content)
	{
		$this->http_equiv[ strtolower($name) ] = array ('name' => $name, 'value' => $content);
	}
	
	/**
	* Задать интервал обновления страницы
	* @param int время в секундах
	* @param string URL куда нужно перенаправить
	*/
	public function refresh($time, $URL)
	{
		$this->httpEquiv('refresh', intval($time) . ';url=' . $URL);
	}
	
	/**
	* Добавить ссылку
	* @param string
	* @param string
	*/
	public function link($rel, $URL)
	{
		$this->link[ $URL ] = array ('rel' => $rel, 'URL' => $URL);
	}
	
	/**
	* Экранировать специальные символы HTML
	* @param string текст для экранирования
	* @return string экранированный текст
	*/
	public static function escape($text)
	{
		return strtr($text, array (
		'<' => '&lt;',
		'>' => '&gt;',
		'&' => '&amp;',
		'"' => '&quot;',
		"'" => '&#39;',
		));
	}
	
	/**
	* Вернуть JavaScript-строку
	* @param string текст строки
	* @retval string строковая константа (JavaScript-код)
	*/
	public static function jstring($text)
	{
		return "'" . addslashes($text) . "'";
	}
	
	/**
	* Установить теги шаблонизатора
	*/
	public function setTemplateTags()
	{
		$this->tpl->set_tag('TITLE', $this->title);
		$this->tpl->set_tag('CSS', $this->css);
		$this->tpl->set_tag('SCRIPTS', $this->script);
		$this->tpl->set_tag('HTTP', $this->http_equiv);
		$this->tpl->set_tag('META', $this->meta);
		$this->tpl->set_tag('LINKS', $this->link);
	}
	
	/**
	* Установить теги шаблонизатора
	*/
	public function pushTemplateTags()
	{
		$this->tpl->save_tags('TITLE', 'CSS', 'SCRIPTS', 'HTTP', 'META', 'LINKS');
		setTemplateTags();
	}
	
	/**
	* Восстановить теги шаблонизатора
	*/
	public function popTemplateTags($tpl)
	{
		$this->tpl->restore_tags();
	}
}

?>