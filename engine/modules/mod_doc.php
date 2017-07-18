<?php

/**
* Модуль управления документами
*
* (с) Золотов Алексей <zolotov-alex@shamangrad.net>, 2009
*/
class mod_doc extends LightModule
{
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	*/
	public function __construct(LightEngine $engine)
	{
		parent::__construct($engine);
		$this->engine->define('MOD_DOC_DIR_DATA', makepath(DIR_ROOT, 'data'));
	}
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_doc($engine);
	}
	
	/**
	* Открыть документ
	* @param string виртуальный путь к документу
	*/
	public function openDocument($path)
	{
		$items = explode('/', $path);
		$i = 0;
		$tables = array ("docs as p0");
		$where = array ("p0.doc_name = ''");
		foreach($items as $item)
		{
			if ( $item !== '' )
			{
				$j = $i++;
				$tables[] = "docs as p$i";
				$where[] = "p$i.doc_name = " . $this->db->quote(UrlDecode($item));
				$where[] = "p$j.doc_id = p$i.doc_parent_id";
			}
		}
		$tables[] = "classes as c";
		$where[] = "p$i.doc_class_id = class_id";
		if ( $doc = $this->db->selectOne($tables, "p$i.*, c.*", implode(' AND ', $where)) )
		{
			return new LightDocument($this->getEngine(), $path, $doc);
		}
		return null;
	}
}

?>