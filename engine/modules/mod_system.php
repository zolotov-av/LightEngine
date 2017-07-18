<?php

/**
* Менеджер системной конфигурации
*
* Простой менеджер конфигурации хранящий свои параметры
* в простом текстовом файле. Используется для хранения статической
* информации такой как максимально/минимально допустимые значения.
*
* (с) Золотов Алексей <zolotov-alex@shamangrad.net>, 2010
*/
class mod_system extends LightModule implements IConfiguration
{
	/**
	* Путь к файлу хранящему конфигурацию
	*/
	protected $path;
	
	/**
	* Кеш конфигурации
	*/
	protected $cache;
	
	/**
	* Режим
	* TRUE - все изменения автоматически фиксируются в БД
	* FALSE - все изменения накапливаются
	*/
	public $autocommint = false;
	
	/**
	* Конструктор менеджера конфигурации
	* @param LightEngine менеджер модулей
	* @param string таблица в БД в которой храниться конфигурация
	*/
	public function __construct(LightEngine $engine, $path = false)
	{
		parent::__construct($engine);
		$this->path = $path ? $path : makepath(DIR_ROOT, 'config.ini');
		$this->reload();
	}
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_system($engine);
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
		$this->cache[$name] = $value;
		if ( $this->autocommint ) $this->commit();
	}
	
	/**
	* Удаление параметра из конфигурации
	* @param string имя удаляемого параметра
	*/
	public function remove($name)
	{
		unset($this->cache[$name]);
		if ( $this->autocommit ) $this->commit();
	}
	
	/**
	* Записать обновления конфигурации в БД
	*/
	public function commit()
	{
		$lines = array();
		foreach($this->cache as $key => $value)
		{
			$lines[] = "$key = $value\n";
		}
		return @ file_put_contents($this->path, implode('', $lines));
	}
	
	/**
	* Перезагрузить конфигурацию из БД
	*/
	public function reload()
	{
		$this->cache = array ();
		$lines = @file($this->path);
		if ( $lines === false ) return false;
		foreach($lines as $line)
		{
			if ( preg_match('/^\s*([A-Za-z_0-9\.]+)\s*=\s*([+-]?\d+(?:\.d+)?)\s*$/', $line, $match) )
			{
				$this->cache[$match[1]] = $match[2];
			}
		}
		return true;
	}
}

?>