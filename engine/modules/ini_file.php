<?php

/**
* Класс для работы с ini-файлами
*
* (c) Zolotov Alex, 2008
*     zolotov-alex@shamangrad.net
*     http://shamangrad.net/
*/
class ini_file
{
	/**
	* Путь к ini-файлу
	*/
	protected $path;
	
	/**
	* Секция опций
	*/
	const OPTIONS = 1;
	
	/**
	* Секция-список
	*/
	const ITEMS = 2;
	
	/**
	* Текущая секция
	*/
	protected $section;
	
	/**
	* Список опеределений типов секций (опции/список)
	*/
	protected $sections;
	
	/**
	* Значения опций
	*/
	protected $options;
	
	/**
	* Значения списков
	*/
	protected $items;
	
	/**
	* Конструктор ini-файла
	* @param string путь к ini-файлу
	*/
	public function __construct($path = false)
	{
		$this->clear();
		if ( $this->path = $path )
		{
			$this->load($path);
		}
	}
	
	/**
	* Загрузить ini-файл
	* @param string путь к ini-файлу
	*/
	public function load($path)
	{
		$this->clear();
		$this->path = realpath($path);
		$text = @ file_get_contents($this->path);
		if ( $text === false )
		{
			throw new Exception(lang('std:read_file_fault', $this->path));
		}
		$this->items = ini_parse_sections($text);
		foreach ($this->items as $name => $lines)
		{
			$this->sections[$name] = self::ITEMS;
			$this->options[$name] = ini_parse_options($lines);
		}
		
	}
	
	/**
	* Сохранить ini-файл
	* @param string путь к файлу
	*/
	public function save($path = false)
	{
		if ( $path !== false )
		{
			$this->path = realpath($path);
		}
		$f = @ fopen($this->path, "wb");
		if ( $f === false )
		{
			throw new Exception(lang('std:write_file_fault', $this->path));
		}
		foreach ($this->sections as $name => $type)
		{
			$line = "[$name]\n";
			$len = strlen($line);
			if ( fwrite($f, $line) < $len )
			{
				throw new Exception(lang('std:write_file_fault', $this->path));
			}
			if ( $type == self::OPTIONS )
			{
				foreach ($this->options[$name] as $key => $value)
				{
					$line = "$key = $value\n";
					$len = strlen($line);
					if ( fwrite($f, $line) < $len )
					{
						throw new Exception(lang('std:write_file_fault', $this->path));
					}
				}
			}
			else // ITEMS
			{
				foreach ($this->items[$name] as $item)
				{
					if ( trim($item) !== '' )
					{
						$line = "$item\n";
						$len = strlen($line);
						if ( fwrite($f, $line) < $len )
						{
							throw new Exception(lang('std:write_file_fault', $this->path));
						}
					}
				}
			}
		}
		fclose($f);
	}
	
	/**
	* Удалить все секции
	*/
	public function clear()
	{
		$this->path = null;
		$this->section = '_default_';
		$this->sections = array ();
		$this->options = array ();
		$this->items = array ();
	}
	
	/**
	* Выбор секции данных
	* @param string имя секции
	*/
	public function set_section($section)
	{
		$this->section = trim($section);
	}
	
	/**
	* Прочитать значение опции из текущей секции
	* @param string имя опции
	* @param mixed значение по-умолчанию, если опции нет
	* @return mixed значение опции или значение по-умолчанию
	*/
	public function read($key, $default = '')
	{
		$tkey = trim($key);
		if ( isset($this->options[$this->section][$tkey]) )
		{
			return $this->options[$this->section][$tkey];
		}
		return $default;
	}
	
	/**
	* Запись значение опции в текущую секцию
	* @param string имя опции
	* @param string значение опции
	*/
	public function write($key, $value)
	{
		$this->sections[$this->section] = self::OPTIONS;
		$this->options[$this->section][trim($key)] = trim($value);
	}
	
	/**
	* Удалить ключ из текущей секции
	* @param string имя удаляемой опции
	*/
	public function delete_key($key)
	{
		$tkey = trim($key);
		if ( isset($this->options[$this->section][$tkey]) )
		{
			$this->sections[$this->section] = self::OPTIONS;
			unset($this->options[$this->section][$tkey]);
		}
	}
	
	/**
	* Удалить секцию
	* @param string имя удаляемой секции
	*/
	public function delete_section($section = false)
	{
		$name = $section === false ? $this->section : trim($section);
		unset($this->sections[$name]);
		unset($this->options[$name]);
		unset($this->items[$name]);
	}
	
	/**
	* Проверить существование секции
	* @param string имя проверяемой секции
	* @return bool TRUE - секция существует, FALSE - не существует
	*/
	public function section_exists($section)
	{
		return isset($this->sections[trim($section)]);
	}
	
	/**
	* Проверить существование опции в текущей секции
	* @param string имя проверяемой опции
	* @return bool TRUE - опция существует, FALSE - не существует
	*/
	public function key_exists($key)
	{
		return isset($this->options[$this->section][trim($key)]);
	}
	
	/**
	* Вернуть число секций
	* @return int число секций
	*/
	public function count_sections()
	{
		return count($this->sections);
	}
	
	/**
	* Число опций в секции
	* @param string имя секции
	* @return int число опций
	*/
	public function count_keys($section = false)
	{
		$name = $section === false ? $this->section : trim($section);
		return isset($this->options[$name]) ? count($this->options[$name]) : 0;
	}
	
	/**
	* Удалить элементы из текущей секции
	* @param int индекс первого удаляемого элемента
	* @param int число удаляемых элементов
	*/
	public function delete_items($itemNo, $count = 1)
	{
		if ( isset($this->items[$this->section]) )
		{
			$this->sections[$this->section] = self::ITEMS;
			array_splice($this->items[$this->section], $itemNo, $count);
		}
	}
	
	/**
	* Удалить элементы из текущей секции
	* @param string удаляемый элемент
	*/
	public function remove_items($item)
	{
		if ( isset($this->items[$this->section]) )
		{
			$this->sections[$this->section] = self::ITEMS;
			$keys = array_keys($this->items[$this->section], $item);
			foreach ($keys as $key)
			{
				unset($this->items[$this->section][$key]);
			}
			$this->items[$this->section] = array_values($this->items[$this->section]);
		}
	}
	
	/**
	* Добавить элемент в конец списка
	* @param string добавляемый элемент
	*/
	public function add_item($item)
	{
		$this->sections[$this->section] = self::ITEMS;
		$this->items[$this->section][] = $item;
	}
	
	/**
	* Вставить элемент
	* @param int позиция вставки (0 - в начало)
	* @param string вставляемый элемент
	*/
	public function insert_item($itemNo, $item)
	{
		$this->sections[$this->section] = self::ITEMS;
		if ( isset($this->items[$this->section]) )
		{
			array_splice($this->items[$this->section], $itemNo, 0, array($item));
		}
		else
		{
			$this->items[$this->section] = array ($item);
		}
	}
	
	/**
	* Заменить элемент
	* @param int номер элемента (нумерация с нуля)
	* @param string новый элемент
	*/
	public function replace_item($itemNo, $item)
	{
		$this->sections[$this->section] = self::ITEMS;
		if ( isset($this->items[$this->section]) )
		{
			array_splice($this->items[$this->section], $itemNo, 1, array($item));
		}
		else
		{
			$this->items[$this->section] = array ($item);
		}
	}
	
	/**
	* Вернуть число элементов в секции
	* @param string имя секции
	* @return int число элементов в секции
	*/
	public function count_items($section = false)
	{
		$name = $section === false ? $this->section : trim($section);
		if ( isset($this->items[$name]) )
		{
			return count($this->items[$name]);
		}
		return 0;
	}
	
	/**
	* Вернуть индекс первого вхождения элемента
	* @param string искомый элемент
	* @return mixed индекс первого вхождения элемента или false,
	* если элемента нет в списке
	*/
	public function index_of($item)
	{
		if ( isset($this->items[$this->section]) )
		{
			return array_search($item, $this->items[$this->section]);
		}
		return false;
	}
	
	/**
	* Вернуть элемент
	* @param int индекс элемента
	* @return string значение элемента или false если индекс не действительный
	*/
	public function get_item($itemNo)
	{
		if ( isset($this->items[$this->section][$itemNo]) )
		{
			return $this->items[$this->section][$itemNo];
		}
		return false;
	}
	
	/**
	* Вернуть список элементов
	* @param string имя секции
	* @return array список элементов
	*/
	public function get_items($section = false)
	{
		$name = $section === false ? $this->section : trim($section);
		if ( isset($this->items[$name]) )
		{
			return $this->items[$name];
		}
		return false;
	}
}

/**
* Парсинг секций ini-файла
* @param string текст ini-файла
* @return array массив секций <имя секции> => <список строк>
*/
function ini_parse_sections($text)
{
	$section = '_default_';
	$sections = array ();
	$lines = explode("\n", $text);
	foreach ($lines as $line)
	{
		$tline = trim($line);
		if ( preg_match('/\\[([^\\]]+)\\]/', $tline, $match) )
		{
			$section = trim($match[1]);
		}
		elseif ( $tline !== '' && $tline{0} !== '#' )
		{
			$sections[ $section ][] = $tline;
		}
	}
	return $sections;
}

/**
* Парсинг опций ini-файла
* @param array список строк
* @return array ассоциативный массив параметров <ключ> => <значение>
*/
function ini_parse_options($lines)
{
	$options = array ();
	foreach ($lines as $line)
	{
		$i = strpos($line, '=');
		if ( $i !== false )
		{
			$name = trim(substr($line, 0, $i));
			if ( strlen($name) > 0 )
			{
				$value = trim(substr($line, $i+1));
				$options[$name] = $value;
			}
		}
		elseif ( strlen($name = trim($line)) > 0 )
		{
			$options[$name] = '';
		}
	}
	return $options;
}

/**
* Парсинг ini-файла
* @param string путь к файлу
* @return array массив <имя секции> => array ( <параметр> => <значение> )
*/
function ini_parse_file($path)
{
	$text = @ file_get_contents($path);
	if ( $text === false )
	{
		throw new Exception(lang('std:read_file_fault', $path));
	}
	$result = array ();
	$sections = ini_parse_sections($text);
	foreach ($sections as $name => $lines)
	{
		$result[$name] = ini_parse_options($lines);
	}
	return $result;
}

?>