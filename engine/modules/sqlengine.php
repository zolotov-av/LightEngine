<?php

/**
* Базовый класс драйвера СУБД
*
* Определяет набор стандартных полей и методов, классу драйвера СУБД
* достаточно определить абстрактные методы
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2007-2009
*
* @package mod_db
*/
abstract class SQLEngine extends LightModule
{
	/**
	* Префикс имен таблиц
	*/
	public $prefix = '';
	
	/**
	* Число выполненых запросов
	*/
	protected $queryCount = 0;
	
	/**
	* Время начала выполения запроса
	*/
	private $startTime = 0;
	
	/**
	* Время выполения запросов
	*/
	private $workTime = 0;
	
	/**
	* Подключение к БД
	* @param mixed параметры подключения к СУБД, состав и
	* и назначение параметров зависит от драйвера СУБД
	*/
	abstract public function connect($args);
	
	/**
	* Закрыть соединение
	*/
	abstract public function close();
	
	/**
	* Выполнить один произвольный SQL-запрос
	* @param string текст SQL-запроса
	* @return mixed набор данных (ресурс или объект, в зависимости от драйвера)
	*/
	abstract public function query($sql);
	
	/**
	* Выполнить запрос без возврата набора данных (типа UPDATE, INSERT...)
	* @param string SQL запрос для выполения
	*/
	abstract public function exec($sql);
	
	/**
	* Выполнить SELECT-запрос
	* @param mixed имя таблицы (строка) или список таблиц (массив)
	* @param string список полей которые нужно извлечь
	* @param string предикат для отбора строк
	* @param string порядок сортировки строк
	* @param int максимальное число возвращаемых строк
	* @param int число строк которые нужно пропустить
	* @return mixed набор данных (ресурс или объект, в зависимости от драйвера)
	* @note поведение при $limit <= 0 && $offset > 0 || $offset < 0 не
	* регламентируется и может приводить к непредсказуемым последвиям.
	*/
	abstract public function select($tables, $fields, $where, $order = "", $limit = 0, $offset = 0);
	
	/**
	* Извлечь очередную строку в виде ассоциативного массива
	* @param mixed набор данных
	* @return array строка данных в виде ассоциативного массива
	*/
	abstract public function fetchAssoc($dataset);
	
	/**
	* Извлечь очередную строку в виде списка
	* @param mixed набор данных
	* @return array строка данных в виде списка значений
	*/
	abstract public function fetchRow($dataset);
	
	/**
	* Освободить данные связанные с набором данных
	* @param mixed набор данных
	*/
	abstract public function freeResult($dataset);
	
	/**
	* Вернуть ID последнией вставленной строки
	* @retval int ID последнией вставленной строки
	*/
	abstract public function lastInsertId();
	
	/**
	* Вернуть число выполненых запросов
	* @return int число выполненых запросов
	*/
	final public function getQueryCount()
	{
		return $this->queryCount;
	}
	
	/**
	* Вернуть общее время затраченое на выполение всех запросов
	* @return int время затраченое на выполение запросов
	*/
	final public function getWorkTime()
	{
		return $this->workTime;
	}
	
	/**
	* Экранировать строку
	* @param string строка для экранирования
	* @return string экранированная строка
	* @note по умолчанию вызывает addslashes()
	*/
	public function escape($text)
	{
		return addslashes($text);
	}
	
	/**
	* Экранировать строку и заглючить в кавычки
	* @param string строка для экранирования
	* @return string экранированная строка заключенная в кавычки
	* @note по умолчанию для экранирования вызывает $this->escape()
	* и заключает строку в двойные кавычки
	*/
	public function quote($text)
	{
		return '"' . $this->escape($text) . '"';
	}
	
	/**
	* Выполнить один произвольный SQL-запрос, возвращает первую строку
	*
	* @note пользователь должен сам добавить выражение LIMIT чтобы ограничить
	*   набор выбираемых строк. Если запрос возвращает более одной строки,
	*   то лишние строки игнорируются и возвращается только первая строка
	*
	* @param string текст SQL-запроса
	* @retval mixed строка в виде ассоциативного массива или false, если
	*   запрос не вернул ни одной строки
	*/
	public function queryOne($sql)
	{
		$r = $this->queryUnbuffered($sql);
		$row = $this->fetchAssoc($r);
		// queryUnbuffered() требует чтобы все строки были прочитаны
		while ( $this->fetchAssoc($r) ) ;
		$this->freeResult($r);
		return $row;
	}
	
	/**
	* Выполнить один произвольный SQL-запрос
	* @param string текст SQL-запроса
	* @retval array массив строк, каждая строка в виде ассоциативного массива
	*/
	public function queryAll($sql)
	{
		$r = $this->queryUnbuffered($sql);
		$result = array ();
		while ( $row = $this->fetchAssoc($r) )
		{
			$result[] = $row;
		}
		$this->freeResult($r);
		return $result;
	}
	
	/**
	* Выборка одной строки
	* @param mixed имя таблицы, список таблиц или join-выражение
	* @param string список полей которые нужно выбрать
	* @param string предикат выбирающий строку
	* @return array строка данных в виде ассоциативного массива
	*/
	public function selectOne($from, $fields, $where)
	{
		$r = $this->select($from, $fields, $where, "", 1);
		$row = $this->fetchAssoc($r);
		$this->freeResult($r);
		return $row;
	}
	
	/**
	* Выполнить SELECT-запрос
	* @param mixed имя таблицы (строка) или список таблиц (массив)
	* @param string список полей которые нужно извлечь
	* @param string предикат для отбора строк
	* @param string порядок сортировки строк
	* @param int максимальное число возвращаемых строк
	* @param int число строк которые нужно пропустить
	* @return mixed набор данных (ресурс или объект, в зависимости от драйвера)
	* @note поведение при $limit <= 0 && $offset > 0 || $offset < 0 не
	* регламентируется и может приводить к непредсказуемым последвиям.
	*/
	public function selectAll($tables, $fields, $where, $order = "", $limit = 0, $offset = 0)
	{
		$r = $this->select($tables, $fields, $where, $order, $limit, $offset);
		$result = array ();
		while ( $row = $this->fetchAssoc($r) )
		{
			$result[] = $row;
		}
		$this->freeResult($r);
		return $result;
	}
	
	/**
	* Подсчитать число строк в таблице
	* @param mixed таблицы записи в которых нужно подсчитать
	* @param string предикат указывающий какие строки нужно считать
	* @return int число строк
	* @note функция аналогична выполнению запроса SELECT COUNT(*)...
	*/
	public function countRows($from, $where)
	{
		$r = $this->select($from, 'COUNT(*)', $where);
		$f = $this->fetchRow($r);
		$this->freeResult($r);
		return intval($f[0]);
	}
	
	/**
	* Вставить строку в таблицу
	*
	* @note значения $field_list нужно экранировать вручную
	*
	* @param string имя таблицы для вставки
	* @param array список полей в виде ассоциативного массива
	*/
	public function insert($table, array $field_list)
	{
		$fields = implode(', ', array_keys($field_list));
		$values = implode(', ', $field_list);
		$this->exec("INSERT INTO {$this->prefix}$table\n($fields) VALUES\n($values)");
	}
	
	/**
	* Вставить строку в таблицу с экранированием всех полей
	*
	* @note все значения $field_list автоматически экранируются и заключаются в кавычки
	*
	* @param string имя таблицы для вставки
	* @param array список полей в виде ассоциативного массива
	*/
	public function insertQuoted($table, array $field_list)
	{
		$fields = implode(', ', array_keys($field_list));
		$values = implode(', ', array_map([$this, "quote"], $field_list));
		$this->exec("INSERT INTO {$this->prefix}$table ($fields) VALUES ($values)");
	}
	
	/**
	* Вставить/заменить строку в таблицу
	*
	* @note этот запрос является нестандартным расширением MySQL, работа
	*   на других СУБД отличных от MySQL не гарантируется
	*
	* @note значения $field_list нужно экранировать вручную
	*
	* @param string имя таблицы для вставки
	* @param array список полей в виде ассоциативного массива
	*/
	public function replace($table, array $field_list)
	{
		$fields = implode(', ', array_keys($field_list));
		$values = implode(', ', $field_list);
		$this->exec("REPLACE INTO {$this->prefix}$table\n($fields) VALUES\n($values)");
	}
	
	/**
	* Вставить/заменить строку в таблицу с экранированием всех полей
	*
	* @note этот запрос является нестандартным расширением MySQL, работа
	*   на других СУБД отличных от MySQL не гарантируется
	*
	* @note все значения $field_list автоматически экранируются и заключаются в кавычки
	*
	* @param string имя таблицы для вставки
	* @param array список полей в виде ассоциативного массива
	*/
	public function replaceQuoted($table, array $field_list)
	{
		$fields = implode(', ', array_keys($field_list));
		$values = implode(', ', array_map([$this, "quote"], $field_list));
		$this->exec("REPLACE INTO {$this->prefix}$table ($fields) VALUES ($values)");
	}
	
	/**
	* Обновить строки таблицы
	* @param string имя обновляемой таблицы
	* @param array список полей в виде ассоциативного массива
	* @param string предикат указывающий какие строки нужно обновить
	* @note значения $field_list нужно экранировать вручную
	*/
	public function update($table, array $field_list, $where)
	{
		$set = array ();
		foreach ($field_list as $f => $v)
		{
			$set[] = "$f = $v";
		}
		$update_str = implode(', ', $set);
		if ( $where = trim($where) ) $where = "\nWHERE $where";
		$this->exec("UPDATE {$this->prefix}$table\nSET $update_str$where");
	}
	
	/**
	* Обновить строки таблицы с экранированием всех полей
	*
	* @note все значения $field_list автоматически экранируются и заключаются в кавычки
	*
	* @param string имя обновляемой таблицы
	* @param array список полей в виде ассоциативного массива
	* @param string предикат указывающий какие строки нужно обновить
	*/
	public function updateQuoted($table, array $field_list, $where)
	{
		$set = array ();
		foreach ($field_list as $f => $v)
		{
			$set[] = "$f = " . $this->quote($v);
		}
		$update_str = implode(', ', $set);
		if ( $where = trim($where) ) $where = "\nWHERE $where";
		$this->exec("UPDATE {$this->prefix}$table\nSET $update_str$where");
	}
	
	/**
	* Удалить строки таблицы
	* @param string имя таблицы из которой удаляются строки
	* @param string предикат указывающий какие строки нужно удалить
	*/
	public function delete($table, $where)
	{
		if ( $where = trim($where) ) $where = "\nWHERE $where";
		$this->exec("DELETE FROM {$this->prefix}$table$where");
	}
	
	/**
	* Вернуть сообщещение об ошибке от драйвера
	* @retval string сообщение ошибке
	*/
	protected function getDriverError()
	{
		return '';
	}
	
	/**
	* Вывести сообщение об ошибке
	* @param string сообщение об ошибке
	* @param string строка запроса с которым связана ошибка
	*/
	protected function error($message, $query = "")
	{
		throw new db_exception($message, get_class($this), $query, $this->getDriverError());
	}
	
	/**
	* Начать подсчет времени
	*/
	final protected function beginTime()
	{
		$this->start = microtime(TRUE);
	}
	
	/**
	* Завершить подсчет времени
	*/
	final protected function endTime()
	{
		$this->workTime += microtime(TRUE) - $this->start;
	}
}

?>