<?php

/**
* Легковесный драйвер PostgreSQL
*
* Данный драйвер реализует интерфейс IDBLight для доступа к базам данных
* сервера PostgreSQL. В силу этого минимализма здесь только низкоуровневые функции
* для выполнения запросов и получения результатов.
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2009
*/
class mod_postgresql extends SQLEngine
{
	/**
	* Идентификатор подключения
	*/
	private $id;
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_postgresql($engine);
	}
	
	/**
	* Подключение к БД
	* @param array параметры подключения
	*/
	public function connect($args)
	{
		$this->close();
		$cargs = array ();
		foreach (array('host', 'port', 'dbname', 'user', 'password') as $key)
		{
			if ( isset($args[$key]) )
			{
				$cargs[] = "$key='" . strtr($args[$key], array ("'" => "\'", "\\" => "\\\\")) . "'";
			}
		}
		$this->prefix = $args['prefix'];
		$persistent = ! empty($args['persistent']);
		$pg_connect = $persistent ? 'pg_connect' : 'pg_pconnect';
		$this->id = @ $pg_connect(implode(' ', $cargs)) or $this->error("Connection error");
		$charset = isset($args['charset']) ? $args['charset'] : 'UTF-8';
		pg_set_client_encoding($this->id, $charset);
	}
	
	/**
	* Закрыть соединение
	*/
	public function close()
	{
		if ( ! is_null($this->id) )
		{
			@ pg_close($this->id) or $this->error("Fail to close connection");
			$this->id = null;
		}
	}
	
	/**
	* Экранировать строку
	* @param string строка для экранирования
	* @retval string экранированная строка
	*/
	public function escape($text)
	{
		return pg_escape_string($this->id, $text);
	}
	
	/**
	* Экранировать строку и заключить в кавычки
	* @param string строка для экранирования
	* @return string экранированная строка заключенная в кавычки
	*/
	public function quote($text)
	{
		return "'" . pg_escape_string($this->id, $text) . "'";
	}
	
	/**
	* Выполнить один произвольный SQL-запрос
	* @param string текст SQL-запроса
	* @retval mixed набор данных (ресурс - набор данных)
	*/
	public function query($sql)
	{
		$this->beginTime();
		$this->queryCount ++;
		$r = @ pg_query($this->id, $sql);
		$this->endTime();
		if ( $r === false ) $this->error("Query error", $sql);
		return $r;
	}
	
	/**
	* Выполнить запрос без возврата набора данных (типа UPDATE, INSERT...)
	* @param string SQL запрос для выполения
	*/
	public function exec($sql)
	{
		$this->beginTime();
		$this->queryCount ++;
		if ( $r = @ pg_query($this->id, $sql) )
		{
			if ( is_resource($r) ) $this->freeResult($r);
		}
		$this->endTime();
		if ( $r === false ) $this->error("Query error", $sql);
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
	public function select($tables, $fields, $where, $order = "", $limit = 0, $offset = 0)
	{
		if ( $where = trim($where) ) $where = "\nWHERE $where";
		if ( $order = trim($order) ) $order = "\nORDER BY $order";
		if ( $limit > 0 || $offset > 0 )
		{
			if ( $limit <= 0 ) $limit = -1;
			$limit = "\nLIMIT " . intval($limit) . " OFFSET " . intval($offset);
		}
		else
		{
			$limit = "";
		}
		$table = is_array($tables) ? implode(", {$this->prefix}", $tables) : $tables;
		return $this->query("SELECT $fields\nFROM {$this->prefix}$table$where$order$limit");
	}
	
	/**
	* Извлечь очередную строку в виде ассоциативного массива
	* @param mixed набор данных
	* @return array строка данных в виде ассоциативного массива
	*/
	public function fetchAssoc($result_id)
	{
		return pg_fetch_assoc($result_id);
	}
	
	/**
	* Извлечь очередную строку в виде списка
	* @param mixed набор данных
	* @return array строка данных в виде списка значений
	*/
	public function fetchRow($result_id)
	{
		return pg_fetch_row($result_id);
	}
	
	/**
	* Освободить данные связанные с набором данных
	* @param mixed набор данных
	*/
	public function freeResult($result_id)
	{
		pg_free_result($result_id) or $this->error("Free result error");
	}
	
	/**
	* Вернуть ID последнией вставленной строки
	* @retval int ID последнией вставленной строки
	*/
	public function lastInsertId()
	{
		$this->begin_time();
		$this->query_count ++;
		$id = false;
		if ( $r = @ pg_query($this->id, "SELECT lastval()") )
		{
			if ( $f = pg_fetch_row($r) ) $id = intval($f[0]);
		}
		$this->end_time();
		return $id;
	}
	
	/**
	* Вернуть сообщещение об ошибке от драйвера
	* @retval string сообщение ошибке
	*/
	protected function getDriverError()
	{
		if ( ! empty($this->id) )
		{
			return pg_last_error($this->id);
		}
		return '';
	}
}

?>