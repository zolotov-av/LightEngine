<?php

/**
* Модуль обработчика шаблонов
*
* Требуется модуль компилятора шаблонов (mod_tplc)
*
* Конфигурация system.php
* - DIR_THEMES - путь к каталогу со темами (скинами)
* - DIR_CACHE - путь к кешу
* - MOD_TPL_MODE - режим обработки шаблонов
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2007-2009
*/
class mod_tpl extends LightModule
{
	/**
	* Флаг режима отладки
	*/
	const MODE_DEBUG = 'debug';
	
	/**
	* Флаг режима оптимизации
	*/
	const MODE_OPT = 'opt';
	
	/**
	* Текущая тема
	*/
	public $theme;
	
	/**
	* Теги
	*/
	protected $tags;
	
	/**
	* Панель управления
	*/
	protected $toolbar;
	
	/**
	* Итератор тегов
	*/
	private $iter;
	
	/**
	* Стек итераторов
	*/
	private $iter_stack;
	
	/**
	* Стек для сохранения тегов
	*/
	private $save_stack;
	
	/**
	* Кеш шаблонов
	*/
	private $cache;
	
	/**
	* Режим шаблонизатора
	*/
	private $mode = mod_tpl::MODE_DEBUG;
	
	/**
	* Время потраченое на обработку шаблонов
	*/
	private $time;
	
	/**
	* Конструктор обработчика шаблонов
	* @param LightEngine менеджер модулей
	* @param string таблица в БД в которой храниться конфигурация
	*/
	public function __construct(LightEngine $engine)
	{
		parent::__construct($engine);
		
		$this->engine->define('DIR_THEMES', makepath(DIR_ROOT, 'themes'));
		$this->engine->define('DIR_CACHE', makepath(DIR_ROOT, 'cache'));
		$this->engine->define('MOD_TPL_MODE', mod_tpl::MODE_DEBUG);
		
		$this->mode = MOD_TPL_MODE;
		$this->theme = 'default';
		$this->cache = array ();
		$this->tags = array ();
		$this->toolbar = array ();
		$this->reset();
		$this->time = 0;
		if ( $this->mode === mod_tpl::MODE_OPT )
		{
			require_once makepath(DIR_CACHE, 'templates.php');
		}
	}
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_tpl($engine);
	}
	
	/**
	* Сброс итератора тегов
	*/
	public function reset()
	{
		$this->iter = & $this->tags;
		$this->iter_stack = array ();
	}
	
	/**
	* Открыть составной тег
	* @param string имя открываемого тега
	*/
	public function open($tag)
	{
		if ( ! isset($this->iter[$tag]) || ! is_array($this->iter[$tag]) )
		{
			$this->iter[$tag] = array ();
		}
		$this->iter_stack[] = & $this->iter;
		$this->iter = & $this->iter[$tag];
	}
	
	/**
	* Закрыть составной тег
	*/
	public function close()
	{
		if ( count($this->iter_stack) > 0 )
		{
			$this->iter = & $this->iter_stack[ count($this->iter_stack)-1 ];
			array_pop($this->iter_stack);
		}
		else
		{
			throw new Exception("unexpected tpl_close() call");
		}
	}
	
	/**
	* Проверить установлен ли тег
	* @param string имя проверяемого тега
	* @return bool TRUE - тег установлен, FASLE - тег не установлен
	*/
	public function is_set($tagName)
	{
		return isset($this->iter[$tagName]);
	}
	
	/**
	* Вернуть тег
	* @param string имя устанавливаемого тега
	* @retval mixed значение тега
	*/
	public function get_tag($tagName)
	{
		return isset($this->iter[$tagName]) ? $this->iter[$tagName] : false;
	}
	
	/**
	* Установить тег
	* @param string имя устанавливаемого тега
	* @param mixed значение тега
	*/
	public function set_tag($tagName, $tagValue)
	{
		$this->iter[$tagName] = $tagValue;
	}
	
	/**
	* Удалить тег
	* @param string имя удаляемого тега
	*/
	public function unset_tag($tagName)
	{
		unset($this->iter[$tagName]);
	}
	
	/**
	* Добавить/установить кнопку в панель управления
	* @param string название кнопки (языковая константа)
	* @param string ссылка кнопки (действие)
	* @param string иконка кнопки (опционально)
	*/
	public function set_button($name, $link, $icon = false)
	{
		$this->toolbar[$name] = array (
		'title' => lang($name),
		'link' => $link,
		'icon' => $icon
		);
	}
	
	/**
	* Отметить кнопку активной
	* @param string название кнопки (языковая константа)
	*/
	public function select_button($name)
	{
		if ( isset($this->toolbar[$name]) ) $this->toolbar[$name]['active'] = true;
	}
	
	/**
	* Отметить кнопку неактивной
	* @param string название кнопки (языковая константа)
	*/
	public function unselect_button($name)
	{
		if ( isset($this->toolbar[$name]) ) $this->toolbar[$name]['active'] = false;
	}
	
	/**
	* Удалить кнопку с панели управления
	* @param string название кнопки (языковая константа)
	*/
	public function remove_button($name)
	{
		unset($this->toolbar[$name]);
	}
	
	/**
	* Добавить значение в тег-список
	* @param mixed добавляемое значение
	*/
	public function append($value)
	{
		$this->iter[] = $value;
	}
	
	/**
	* Сохранить теги
	*
	* Функция принимает произвольное число параметров - имен тегов
	*/
	public function save_tags($tag1 /* $tag2, ... */)
	{
		$tags = func_get_args();
		$chunk = array ();
		foreach ($tags as $tag)
		{
			$name = strtolower($tag);
			$chunk[$name] = @ $this->tags[$name];
		}
		$this->save_stack[] = $chunk;
	}
	
	/**
	* Восстановить теги из стека
	*/
	public function restore_tags()
	{
		if ( count($this->save_stack) == 0 )
		{
			throw new Exception('tags\' stack is empty');
		}
		$chunk = array_pop($this->save_stack);
		foreach ($chunk as $name => $value)
		{
			$this->tags[$name] = $value;
		}
	}
	
	/**
	* Установить INFO-тег
	* @param string имя тега
	* @param mixed значение тега
	*/
	public function setInfo($name, $value)
	{
		$this->tags['INFO'][$name] = $value;
	}
	
	/**
	* Обработка шаблона
	* @param string шаблон
	* @return string обработанный текст
	*/
	public function render($template)
	{
		return $this->process($template, $this->tags);
	}
	
	/**
	* Обработка шаблона в режиме отладки
	* @param string шаблон для обработки
	* @param mixed теги
	*/
	protected function process_debug($template, $tags)
	{
		if ( isset($this->cache[$this->theme][$template]) )
		{
			return @ $this->cache[$this->theme][$template] ($this, $tags);
		}
		
		$path = DIR_THEMES . "/{$this->theme}/{$template}.tpl";
		$cache = DIR_CACHE . "/tpl/" . md5("{$this->theme}/{$template}") . ".ctpl";
		if ( ! file_exists($path) )
		{
			$path = DIR_THEMES . "/default/{$template}.tpl";
			$cache = DIR_CACHE . "/tpl/" . md5("default/{$template}") . ".ctpl";
			if ( ! file_exists($path) )
			{
				throw new Exception("template not found: $template");
			}
		}
		
		if ( ! file_exists($cache) || filemtime($path) > filemtime($cache) )
		{
			$code = $this->tplc->compile(file_get_contents($path), $path);
			file_put_contents($cache, $code);
		}
		else
		{
			$code = file_get_contents($cache);
		}
		
		$func = create_function('$tpl, $tags', $code);
		$this->cache[$this->theme][$template] = $func;
		return @ $func($this, $tags);
	}
	
	/**
	* Обработка шаблона в режиме отладки
	* @param string шаблон для обработки
	* @param mixed теги
	*/
	protected function process_opt($template, $tags)
	{
		$vpath = preg_replace('{/+}', '/', "/{$this->theme}/$template.tpl");
		$func = "tpl_" . md5($vpath);
		if ( function_exists($func) )
		{
			return @ $func($this, $tags);
		}
		$vpath = preg_replace('{/+}', '/', "/default/$template.tpl");
		$func = "tpl_" . md5($vpath);
		if ( function_exists($func) )
		{
			return @ $func($this, $tags);
		}
		throw new Exception("template not found: $template");
	}
	
	/**
	* Обработка шаблона
	* @param string шаблон для обработки
	* @param mixed теги
	*/
	public function process($template, $tags)
	{
		return $this->mode === self::MODE_OPT ? $this->process_opt($template, $tags) : $this->process_debug($template, $tags);
	}
}

?>