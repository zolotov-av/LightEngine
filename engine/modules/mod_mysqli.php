<?php

/**
* Легковесный драйвер MySQL версии 4.1 и выше
*
* Данный драйвер реализует интерфейс IDBLight для доступа к базам данных
* сервера MySQL. В силу этого минимализма здесь только низкоуровневые функции
* для выполнения запросов и получения результатов.
*
* (c) Zolotov Alex, 2007-2012
*     zolotov-alex@shamangrad.net
*     http://shamangrad.net/
*/
class mod_mysqli extends SQLEngine
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
		return new mod_mysqli($engine);
	}
	
	/**
	* Деструктор модуля
	*/
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
		$this->prefix = $args['prefix'];
		$this->beginTime();
		$this->id = new mysqli($args['host'], $args['user'], $args['password'], $args['database']);
		$this->endTime();
		if ( $this->id->connect_error )
		{
			$this->id = null;
			$this->error("Connection error");
		}
		$charset = isset($args['set_names']) ? $args['set_names'] : 'utf8';
		$this->id->set_charset($charset);
		$this->exec("SET NAMES $charset");
	}
	
	/**
	* Закрыть соединение
	*/
	public function close()
	{
		if ( $this->id )
		{
			$status = $this->id->close();
			if ( ! $status ) $this->error("Fail to close connection");
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
		return $this->id->real_escape_string($text);
	}
	
	/**
	* Экранировать строку и заключить в кавычки
	* @param string строка для экранирования
	* @return string экранированная строка заключенная в кавычки
	*/
	public function quote($text)
	{
		return '"' . $this->id->real_escape_string($text) . '"';
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
		$r = $this->id->query($sql);
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
		$r = $this->id->query($sql, MYSQLI_USE_RESULT);
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
		$r = $this->id->query($sql);
		$this->endTime();
		if ( $r === true ) return true;
		if ( $r === false ) $this->error("Query error", $sql);
		$this->freeResult($r);
		return true;
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
		return $result_id->fetch_assoc();
	}
	
	/**
	* Извлечь очередную строку в виде списка
	* @param mixed набор данных
	* @return array строка данных в виде списка значений
	*/
	public function fetchRow($result_id)
	{
		return $result_id->fetch_row();
	}
	
	/**
	* Освободить данные связанные с набором данных
	* @param mixed набор данных
	*/
	public function freeResult($result_id)
	{
		$result_id->free_result();
	}
	
	/**
	* Вернуть ID последнией вставленной строки
	* @retval int ID последнией вставленной строки
	*/
	public function lastInsertId()
	{
		return $this->id->insert_id;
	}
	
	/**
	* Вернуть сообщещение об ошибке от драйвера
	* @retval string сообщение ошибке
	*/
	protected function getDriverError()
	{
		if ( ! empty($this->id) )
		{
			if ( $this->id->errno )
			{
				return $this->id->error;
			}
		}
		return '';
	}
	
	/**
	* Начать транзакцию
	*/
	public function begin()
	{
		$this->exec("BEGIN");
	}
	
	/**
	* Подтвердить транзакцию
	*/
	public function commit()
	{
		$this->exec("COMMIT");
	}
	
	/**
	* Откатить транзакцию
	*/
	public function rollback()
	{
		$this->exec("ROLLBACK");
	}
}

?>