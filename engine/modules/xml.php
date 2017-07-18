<?php

if ( ! defined('DEBUG') ) define('DEBUG', false);

/**
* Простой компонент для генерации XML-файлов
*
* (c) Zolotov Alex, 2007-2009
*     zolotov-alex@shamangrad.net
*     http://shamangrad.net/
*/
class XML
{
	/**
	* Атрибуты тега
	*/
	public $attrs = array ();
	
	/**
	* Отступы
	*/
	public $Indents = false;
	
	/**
	* Отступ
	*/
	private $indent = '';
	
	/**
	* Текст XML-файла
	*/
	private $content = '';
	
	/**
	* Стек открытых тегов
	*/
	private $stack = array ();
	
	/**
	* Признак незавершенного тега
	*/
	private $open = false;
	
	/**
	* Тег - пустой
	*/
	private $empty = true;
	
	/**
	* Конструктор компонента XML
	*/
	public function __construct()
	{
		$this->content = '';
		$this->stack = array ();
		$this->open = false;
		$this->empty = true;
		if ( $this->indents = DEBUG )
		{
			$this->indent = "\n";
		}
		else
		{
			$this->indent = "";
		}
	}
	
	/**
	* Записать атрибуты тега
	*/
	private function write_attrs()
	{
		if ( $this->open )
		{
			foreach ($this->attrs as $name => $value)
			{
				$this->content .= ' ' . $name . '="' . htmlspecialchars($value) . '"';
			}
		}
	}
	
	/**
	* Открыть тег
	* @param string имя открываемого тега
	*/
	public function open($tagName)
	{
		$this->flush();
		$this->attrs = array ();
		$this->stack[] = $tagName;
		$this->content .= $this->indent . '<' . $tagName;
		$this->indent .= " ";
		$this->open = true;
		$this->empty = true;
	}
	
	/**
	* Закрыть тег
	* @param string имя закрываемого тега
	* @note теги должны закрываться в соответствующем порядке, в противном
	*   случае будет генерироваться исключение
	*/
	public function close($tagName)
	{
		if ( count($this->stack) == 0 )
		{
			throw new Exception("unexpected call XML::close($tagName), no tags opened");
		}
		$openTag = array_pop($this->stack);
		if ( $openTag !== $tagName )
		{
			throw new Exception("unexpected call XML::close($tagName), expected XML::close($openTag)");
		}
		$this->indent = substr($this->indent, 0, strlen($this->indent)-1);
		if ( $this->open )
		{
			$this->write_attrs();
			$this->content .= ' />';
			$this->open = false;
		}
		else
		{
			if ( $this->empty ) $this->content .= $this->indent;
			$this->content .= "</$tagName>";
			$this->empty = true;
		}
	}
	
	/**
	* Записать накопленный данные в выходной буфер
	*/
	public function flush()
	{
		if ( $this->open )
		{
			$this->write_attrs();
			$this->content .= '>';
			$this->open = false;
		}
	}
	
	/**
	* Записать текст
	* @param string текст для записи
	*/
	public function text($text)
	{
		if ( strlen($text) > 0 )
		{
			$this->flush();
			$this->empty = false;
			$this->content .= htmlspecialchars($text);
		}
	}
	
	/**
	* Записать простой тег
	* @param string имя тега
	* @param mixed значение тега (строка или число)
	*/
	public function tag($tagName, $tagValue)
	{
		$this->open($tagName);
		$this->text($tagValue);
		$this->close($tagName);
	}
	
	/**
	* Вернуть контент сформированного XML-файла
	* @param bool TRUE - вернуть фрагмент, FALSE (по умолчанию) - полный документ
	* @return string контент XML-файла (text/xml)
	* @note если закрыты не все теги, то будет сгенерировано исключение
	*/
	public function getContent($fragment = false)
	{
		if ( count($this->stack) > 0 )
		{
			throw new Exception("unexpected call MXWriter::getResult(), some tags opened");
		}
		if ( $fragment ) return $this->content;
		return "<?xml version=\"1.0\" ?>" . $this->content;
	}
}

?>