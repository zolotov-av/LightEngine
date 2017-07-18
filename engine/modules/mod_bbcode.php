<?php

/**
* Двухпроходный обработчик bb-кодов
*
*  (cc-by) Zolotov Alex, 2007 - 2008, 2010
*          zolotov-alex@shamangrad.net
*          http://shamangrad.net/
*/
class mod_bbcode extends LightModule
{
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_bbcode($engine);
	}
	
	/**
	* экранирование спецсимволов и "типографские" замены
	*/
	public static function escape($text)
	{
		static $bb = array (
		'&' => '&amp;',
		'<<' => '&#171;',
		'>>' => '&#187;',
		'<' => '&lt;',
		'>' => '&gt;',
		'[' => '&#91;',
		']' => '&#93;',
		'(c)' => '&copy;',
		'"' => '&quot;',
		' ---' => '&nbsp;&mdash;',
		'---' => '&mdash;',
		' --' => '&nbsp;&ndash;',
		'--' => '&ndash;',
		'...' => '&hellip;',
		);
		
		return str_replace(array_keys($bb), array_values($bb), $text);
	}
	
	public static function unescape($text)
	{
		return strtr($text, array (
		'&amp;' => '&',
		'&#171;' => '<<',
		'&#187;' => '>>',
		'&lt;' => '<',
		'&gt;' => '>',
		'&#91;' => '[',
		'&#93;' => ']',
		'&copy;' => '(c)',
		'&quot;' => '"',
		'&nbsp;&mdash;' => ' ---',
		'&mdash;' => '---',
		'&nbsp;&ndash;' => ' --',
		'&ndash;' => '--',
		'&hellip;' => '...'
		));
	}
	
	public static function escapePlain($text)
	{
		return strtr($text, array (
		'<' => '&lt;',
		'>' => '&gt;',
		'&' => '&amp;',
		'"' => '&quot;',
		'[' => '&#91;',
		']' => '&#93;'
		));
	}
	
	/*! TODO: требуется ревизия по поводу безопасности */
	public static function escapeURL($URL)
	{
		if ( preg_match("#^((https?|ftp|svn)://)?([A-Za-z_0-9\\-]+)(@?)(.*)$#", $URL, $match) )
		{
			if ( ! isset($match[1]) )
			{
				$schema = $match[4] == "@" ? "mailto:" : "http://";
			}
			else
			{
				$schema = $match[1] ? $match[1] : "http://";
			}
			return $schema . $match[3] . $match[4] . $match[5];
		}
		return false;
	}
	
	/**
	* Выделение ссылок
	*
	* TODO: требуется ревизия по поводу безопасности
	*/
	public static function makelinks($text)
	{
		return preg_replace('#((https?|ftps?):(//)?[A-Z0-9@\\/\#%$\.=:&_\?@;~-]+)(?![^<]*?>)#i','<a href="$1" target="_blank">$1</a>', $text);
	}
	
	/**
	* Первый проход обработчика bb-кодов
	*/
	public function pass1($text, $mode = 'classic')
	{
		$bb = new bbcode;
		$code = $bb->process($text, $mode);
		return $code;
	}
	
	/**
	* Второй проход обработчика bb-кодов
	*/
	public function pass2($bbtext)
	{
		return preg_replace('/\[.*?]/s', '', $bbtext);
	}
	
	/**
	* получить чистый текст (text/plain) для индексации
	*/
	function plain($bbtext)
	{
		$bbtext = preg_replace('/\\[+].*?\\[-]/s',  '', $bbtext);
		$bbtext = preg_replace('/\\[.*?]/s', '', $bbtext);
		return $this->unescape(preg_replace('/<.*?>/s',  '', $bbtext));
	}
	
	/**
	* Предпросмотр обработки bb-кодов (выполняется оба прохода)
	*/
	public function preview($text, $mode = 'classic')
	{
		return $this->pass2($this->pass1($text, $mode));
	}
	
	/**
	* Восстановление bb-кодов (преобразование обратное первому проходу)
	*/
	public function uncode($bbtext)
	{
		$text = preg_replace('/\[\+].*?\[-]/s',  '', $bbtext);
		return $this->unescape(preg_replace('/<.*?>/s',  '', $text));
	}
	
	/**
	* Подсветка синтаксиса
	*/
	public function highlight($style, $text)
	{
		static $exists = array ();
		
		if ( ! preg_match('/^[A-Za-z_0-9]+$/', $style) )
		{
			return $this->escapePlain($text);
		}
		
		$func = "highlight_$style";
		
		if ( ! isset($exists[$style]) )
		{
			if ( function_exists($func) )
			{
				$exists[$style] = true;
			}
			else
			{
				$path = DIR_HIGHLIGHTS . "/$style.php";
				if ( $exists[$style] = is_readable($path) )
				{
					require_once $path;
					if ( ! function_exists($func) )
					{
						trigger_error("wrong bbcode highlighter script", E_USER_ERROR);
					}
				}
				else
				{
					return $this->escapePlain($text);
				}
			}
		}
		
		return strtr(@ call_user_func($func, $text), array (
		'[' => '&#91;',
		']' => '&#93;'
		));
	}
}

?>