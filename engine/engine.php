<?php

/**
* Light Engine
*
* (с) Золотов Алексей <zolotov-alex@shamangrad.net>
* @package LightEngine
*/

/**
* Построить путь
*/
function makepath($path, $dir /* , ... */)
{
	$args = func_get_args();
	return implode(DIRECTORY_SEPARATOR, $args);
}

/**
* LightEngine
*
* Центральный класс движка - связующее звено
* для всех остальных компонентов
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2008-2009
*/
class LightEngine
{
	/**
	* Список загруженных модулей
	*/
	private $modules = array ();
	
	/**
	* Конструктор
	* @param string список путей поиска модулей (разделённых двоеточием)
	*/
	public function __construct()
	{
		$this->modules = array ('engine' => $this);
	}
	
	/**
	* Объявить константу, если она не объявлена
	*
	* Аналог стандартной функции define(), но в отличии от неё
	* не выдает ошибки, если константа уже объявлена
	*
	* @param string название константы
	* @param string значение константы
	*/
	public function define($name, $value)
	{
		if ( ! defined($name) )
		{
			define($name, $value);
		}
	}
	
	/**
	* Найти и загрузить класс
	* @param string имя класса
	*/
	public static function loadClass($class)
	{
		static $dirs = false;
		
		if ( ! $dirs )
		{
			$dirs = array ();
			foreach(explode(':', DIR_PATH) as $dir)
			{
				if ( $full = realpath(substr($dir, 0, 1) === '/' ? $dir : makepath(DIR_ROOT, $dir)) )
					$dirs[] = $full;
			}
		}
		
		$fileName = strtolower($class) . '.php';
		
		foreach($dirs as $dir)
		{
			
			$path = makepath($dir, $fileName);
			if ( file_exists($path) )
			{
				require_once $path;
				return true;
			}
		}
		
		throw new Exception("class $class not found");
	}
	
	/**
	* Найти модуль
	*
	* Если модуль загружен, то вернуть сразу, если нет,
	* то найти, загрузить и вернуть. Если модуль найти
	* не удаётся, то снегерировать исключение
	* @param string имя модуля
	* @retval LightModule модуль
	*/
	public function lookup($module)
	{
		$module = strtolower($module);
		if ( isset($this->modules[$module]) ) return $this->modules[$module];
		$class = "mod_$module";
		self::loadClass($class);
		return $this->modules[$module] = call_user_func(array($class, 'create'), $this);
	}
	
	/**
	* Перегрузка чтения свойств
	* @param string имя модуля
	* @retval LightModule модуль
	*/
	final public function __get($module)
	{
		return $this->lookup($module);
	}
}

/**
* Базовый класс компонента
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2009
*/
class LightComponent
{
	/**
	* Движок (менеджер модулей)
	* @var LightEngine
	*/
	private $engine;
	
	/**
	* Конструктор компонента
	* @param LightEngine движок
	*/
	public function __construct(LightEngine $engine)
	{
		$this->engine = $engine;
	}
	
	/**
	* Вернуть движок
	* @retval LightEngine движок
	*/
	final public function getEngine()
	{
		return $this->engine;
	}
	
	/**
	* Вернуть модуль
	*
	* Если модуль не загружен, то он автоматически загружается
	*
	* @param string имя модуля
	* @retval LightModule модуль
	*/
	final public function __get($name)
	{
		return $this->engine->lookup($name);
	}
}

/**
* Базовый класс модулей
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2009
*/
class LightModule extends LightComponent
{
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine) { }
}

/**
* Базовый класс аплетов
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2009
*/
class LightApplet extends LightComponent
{
}

?>