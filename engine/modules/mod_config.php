<?php

/**
* Менеджер конфигурации
*
* Простой менеджер конфигурации хранящий свои параметры
* в базе данных.
*
* (с) Золотов Алексей <zolotov-alex@shamangrad.net>, 2008-2009
*/
class mod_config extends LightModule implements IConfiguration
{
	/**
	* Таблица в БД в которой храниться конфигурация
	*/
	protected $table;
	
	/**
	* Кеш конфигурации
	*/
	protected $cache;
	
	/**
	* Параметры ожидающие обновления
	*/
	protected $update = array ();
	
	/**
	* Параметры ожидающие удаления
	*/
	protected $remove = array ();
	
	/**
	* Режим
	* TRUE - все изменения автоматически фиксируются в БД
	* FALSE - все изменения накапливаются
	*/
	public $autocommit = true;
	
	/**
	* Конструктор менеджера конфигурации
	* @param LightEngine менеджер модулей
	* @param string таблица в БД в которой храниться конфигурация
	*/
	public function __construct(LightEngine $engine, $table = 'config')
	{
		parent::__construct($engine);
		$this->table = $table;
		$this->reload();
	}
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_config($engine);
	}
	
	/**
	* Проверить существование параметра
	* @param string имя проверяемого параметра
	* @return bool TRUE - параметр определен, FALSE - параметр не определен
	*/
	public function exists($name)
	{
		return isset($this->cache[$name]);
	}
	
	/**
	* Чтение параметра конфигурации
	* @param string имя читаемого параметра
	* @param mixed значение по умолчанию, если параметра нет
	* @return mixed прочитанное значение параметра конфигурации
	*/
	public function read($name, $default = false)
	{
		return isset($this->cache[$name]) ? $this->cache[$name] : $default;
	}
	
	/**
	* Запись параметра конфигурации
	* @param string имя записываемого параметра
	* @param string записываемое значение параметра
	* @note если параметра нет, то он автоматически создается
	*/
	public function write($name, $value)
	{
		$this->update[$name] = $this->cache[$name] = $value;
		unset($this->remove[$name]);
		if ( $this->autocommit ) $this->commit();
	}
	
	/**
	* Удаление параметра из конфигурации
	* @param string имя удаляемого параметра
	*/
	public function remove($name)
	{
		unset($this->cache[$name], $this->update[$name]);
		$this->remove[$name] = 1;
		if ( $this->autocommit ) $this->commit();
	}
	
	/**
	* Записать обновления конфигурации в БД
	*/
	public function commit()
	{
		if ( count($this->update) > 0 )
		{
			$list = implode(', ', array_map(array($this->db, "quote"), array_keys($this->update)));
			$r = $this->db->select($this->table, 'config_name', "config_name IN ($list)");
			while ( $row = $this->db->fetchAssoc($r) )
			{
				$this->db->update($this->table, array (
				"config_value" => $this->db->quote($this->update[$row['config_name']])
				), "config_name = " . $this->db->quote($row['config_name']));
				unset($this->update[$row['config_name']]);
			}
			$this->db->freeResult($r);
			foreach ($this->update as $name => $value)
			{
				$this->db->insert($this->table, array (
				'config_name' => $this->db->quote($name),
				'config_value' => $this->db->quote($value)
				));
			}
			$this->update = array ();
		}
		
		if ( count($this->remove) > 0 )
		{
			$list = implode(", ", array_map(array($this->db, "quote"), array_keys($this->remove)));
			$this->db->delete($this->table, "config_name IN ($list)");
			$this->remove = array ();
		}
	}
	
	/**
	* Перезагрузить конфигурацию из БД
	*/
	public function reload()
	{
		$r = $this->db->select($this->table, 'config_name, config_value', '');
		while ( $row = $this->db->fetchAssoc($r) )
		{
			$this->cache[ $row['config_name'] ] = $row['config_value'];
		}
		$this->db->freeResult($r);
		$this->update = array ();
		$this->remove = array ();
	}
}

?>