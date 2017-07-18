<?php

/**
* Легковесный драйвер MySQL версии 4.1 и выше
*
* Данный драйвер реализует интерфейс IDBLight для доступа к базам данных
* сервера MySQL. В силу этого минимализма здесь только низкоуровневые функции
* для выполнения запросов и получения результатов.
*
* (c) Zolotov Alex, 2007-2009
*     zolotov-alex@shamangrad.net
*     http://shamangrad.net/
*/
class mod_mysql extends SQLEngine
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
		return new mod_mysql($engine);
	}
	
	public function __construct(LightEngine $engine)
	{
		parent::__construct($engine);
		$this->id = false;
	}
	
	public function __destruct()
	{
		$this->close();
	}
	
	/**
	* Подключение к БД
	* @param array параметры подключения
	*/
	public function connect($args)
	{
		$this->close();
		foreach (array('persistent', 'host', 'user', 'password') as $key)
		{
			if ( ! isset($args[$key]) ) $args[$key] = '';
		}
		$this->prefix = $args['prefix'];
		$persistent = ! empty($args['persistent']);
		$mysql_connect = $persistent ? 'mysql_pconnect' : 'mysql_connect';
		$this->id = @ $mysql_connect($args['host'], $args['user'], $args['password']) or $this->error("Connection error");
		$charset = isset($args['set_names']) ? $args['set_names'] : 'utf8';
		@ mysql_query("SET NAMES $charset", $this->id) or $this->error("Fail to set character set");
		@ mysql_select_db($args['database'], $this->id) or $this->error("Fail to select database $args[database]");
	}
	
	/**
	* Закрыть соединение
	*/
	public function close()
	{
		if ( empty($this->id) ) return;
		@ mysql_close($this->id) or $this->error("Fail to close connection");
		$this->id = null;
	}
	
	/**
	* Экранировать строку
	* @param string строка для экранирования
	* @retval string экранированная строка
	*/
	public function escape($text)
	{
		return mysql_real_escape_string($text, $this->id);
	}
	
	/**
	* Экранировать строку и заключить в кавычки
	* @param string строка для экранирования
	* @return string экранированная строка заключенная в кавычки
	*/
	public function quote($text)
	{
		return '"' . mysql_real_escape_string($text, $this->id) . '"';
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
		$r = @ mysql_query($sql, $this->id);
		$this->endTime();
		if ( $r === false ) $this->error("Query error", $sql);
		return $r;
	}
	
	/**
	* Выполнить один произвольный SQL-запрос
	* 
	* Нестандартное расширение - небуферизованный запрос
	*
	* @NOTE Клиент обязан прочитать все строки, прежде чем
	* делать следующий запрос
	* 
	* @param string текст SQL-запроса
	* @retval mixed набор данных (ресурс - набор данных)
	*/
	public function queryUnbuffered($sql)
	{
		$this->beginTime();
		$this->queryCount ++;
		$r = @ mysql_unbuffered_query($sql, $this->id);
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
		if ( $r = @ mysql_query($sql, $this->id) )
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
		return mysql_fetch_assoc($result_id);
	}
	
	/**
	* Извлечь очередную строку в виде списка
	* @param mixed набор данных
	* @return array строка данных в виде списка значений
	*/
	public function fetchRow($result_id)
	{
		return mysql_fetch_row($result_id);
	}
	
	/**
	* Освободить данные связанные с набором данных
	* @param mixed набор данных
	*/
	public function freeResult($result_id)
	{
		mysql_free_result($result_id) or $this->error("Free result error");
	}
	
	/**
	* Вернуть ID последнией вставленной строки
	* @retval int ID последнией вставленной строки
	*/
	public function lastInsertId()
	{
		return mysql_insert_id($this->id);
	}
	
	/**
	* Вернуть сообщещение об ошибке от драйвера
	* @retval string сообщение ошибке
	*/
	protected function getDriverError()
	{
		if ( ! empty($this->id) )
		{
			if ( mysql_errno($this->id) )
			{
				return mysql_error($this->id);
			}
		}
		return '';
	}
	
	/**
	* Начать транзакцию
	*/
	public function begin()
	{
		$this->query("BEGIN");
	}
	
	/**
	* Подтвердить транзакцию
	*/
	public function commit()
	{
		$this->query("COMMIT");
	}
	
	/**
	* Откатить транзакцию
	*/
	public function rollback()
	{
		$this->query("ROLLBACK");
	}
}

?>