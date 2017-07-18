<?php

/**
* Функции для работы с языковыми константами
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2007-2009
*/

class mod_lang extends LightModule
{
	/**
	* Текущий язык
	* @var string
	*/
	private $current;
	
	/**
	* Кеш языковых констант
	* @var array
	*/
	private static $cache = array ();
	
	/**
	* Стек
	* @var array
	*/
	private $stack;
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	*/
	public function __construct(LightEngine $engine)
	{
		parent::__construct($engine);
		
		$this->engine->define('DIR_LANGS', makepath(DIR_ROOT, 'lang'));
		$this->engine->define('LANG_DEFAULT', 'russian');
		
		$this->current = LANG_DEFAULT;
		$this->stack = array ();
	}
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_lang($engine);
	}
	
	/**
	* Вернуть текущий язык
	* @retval string идентификатор текущего языка
	*/
	public function getCurrentLanguage()
	{
		return $this->current;
	}
	
	/**
	* Переключить текущий язык
	* @param string идентификатор языка
	*/
	public function setCurrentLanguage($language)
	{
		$this->current = $language;
	}
	
	/**
	* Временно переключить текущий язык
	*
	* Текущий язык сохраняется в стеке и переключается на указанный.
	* Чтобы вернуть текущий язык обратно используйте функцию end()
	*
	* @param string идентификатор языка
	*/
	public function begin($language)
	{
		array_push($this->stack, $this->current);
		$this->current = $language;
	}
	
	/**
	* Вернуться к передыдущему языку
	*
	* Текущий язык переключается на язык извлеченный из стека, если стек пуст,
	* то генерируется исключение
	*/
	public function end()
	{
		if ( count($this->stack) == 0 )
		{
			throw new Exception('unexpected call of mod_lang::end(): language stack is empty');
		}
		$this->current = array_pop($this->stack);
	}
	
	/**
	* Вернуть языковую константу
	*
	* @param string строковый идентификатор константы в виде (<файл>:<константа>)
	* @retval string значение языковой константы
	* @note Функция принимает произвольное число дополнительных аргументов для
	* подстановки их в языковую константу
	*/
	public function format($id /* args */)
	{
		$args = func_get_args();
		return $this->formatExt($id, $args, 1);
	}
	
	/**
	* Парсинг файла локализации
	* @param string путь к файлу локализации
	* @retval array языковые константы
	*/
	protected static function parse($path)
	{
		$lines = @ file($path);
		if ( $lines === false )
		{
			throw new Exception("language file read fault: $path");
		}
		$lang = array ('_' => '');
		$last = '_';
		$lineNo = 0;
		foreach($lines as $line)
		{
			$lineNo ++;
			if ( preg_match("/^\s*([a-zA-Z_0-9]+)\s*=(.*)$/", $line, $match) )
			{
				$lang[$last = $match[1]] = trim($match[2]);
			}
			elseif ( ! preg_match('/^\s*(#|$)/', $line) )
			{
				throw new Exception("Language file bad format\nFile: $path\nLine: $lineNo");
			}
		}
		unset($lang['_']);
		return $lang;
	}
	
	/**
	* Вернуть языковую константу
	* @param string строковый идентификатор константы в виде (<файл>:<константа>)
	* @param array список параметров для подстановки в языковую константу
	* @param integer индекс первого параметра в списке
	* @retval string значение языковой константы
	*/
	public function formatExt($id, $args, $offset)
	{
		if ( empty($id) ) return '';
		if ( ! preg_match('/^([A-Za-z_0-9\\-]+):([A-Za-z_0-9]+)$/', $id, $match) )
		{
			throw new Exception("wrong lang message ID: \"$id\"");
		}
		
		$file = $match[1];
		$mesid = $match[2];
		if ( ! isset(mod_lang::$cache[$this->current][$file]) )
		{
			mod_lang::$cache[$this->current][$file] = mod_lang::parse(DIR_LANGS . "/{$this->current}/$file.lng");
		}
		if ( ! isset(mod_lang::$cache[$this->current][$file][$mesid]) )
		{
			throw new Exception("Language constant $id not defined in language {$this->current}");
		}
		return format_string_ex(mod_lang::$cache[$this->current][$file][$mesid], $args, $offset);
	}
}

/**
* Вспомогательный класс для функций форматирования строк
*/
class aux_format
{
	/** список параметров */
	var $args;
	
	/** индекс первого парамера в списке */
	var $offset;
	
	/** Функция обратного вызова осуществляющая подстановки */
	function callback($match)
	{
		if ( isset($match[5]) ) $x = intval($match[5]);
		else if ( isset($match[4]) ) $x = intval($match[4]);
		else if ( isset($match[3]) ) $x = intval($match[3]);
		else return '%';
		$x = isset($this->args[$x + $this->offset]) ? $this->args[$x + $this->offset] : '';
		return isset($match[5]) && $match[5] !== '' ? htmlspecialchars($x) : $x;
	}
}

/**
* Форматировать строку
* @param string строка для форматирования
* @return оторматированная строка
* @note функция принимает произвольное число дополнительных параметров
* для подстановки в строку
*/
function format_string($fmt /* args */)
{
	$args = func_get_args();
	return format_message_ex($fmt, $args, 1);
}

/**
* Форматировать строку
* @param string строка для форматирования
* @param array список параметров для подстановки
* @param integer индекс первого параметра в списке
* @return оторматированная строка
*/
function format_string_ex($fmt, $args, $offset)
{
	$aux = new aux_format;
	$aux->args = $args;
	$aux->offset = intval($offset) - 1;
	return preg_replace_callback('/%((%)|(\d+)|\{(\d+)\}|<(\d+)>)/', array(& $aux, 'callback'), $fmt);
}

?>