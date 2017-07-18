<?php

/**
* Модуль управления правами доступа
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2010
*/

class mod_access extends LightModule
{
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	*/
	public function __construct(LightEngine $engine)
	{
		parent::__construct($engine);
	}
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_access($engine);
	}
	
	/**
	* Проверить доступ текущего пользователя к указаной привилегии
	* @param string идентификатор привилегии
	*/
	public function checkAccess($privilege)
	{
		$user = $this->session->authorize();
		$priv = $this->db->selectOne(array("privileges", "grants"), "privilege_id",
			"grant_privilege_id = privilege_id
			AND privilege_name = " . $this->db->quote($privilege) . "
			AND grant_user_id = " . intval($user['user_id']));
		return (bool) $priv;
	}
	
	/**
	* Проверить доступ текущего пользователя к указаной привилегии
	*
	* Если доступа нет, то сгенерировать исключение EForbiden
	* @param string идентификатор привилегии
	*/
	public function requireAccess($privilege)
	{
		if ( ! $this->checkAccess($privilege) )
		{
			$this->tpl->set_tag('CONTENT', 'std/forbiden');
			$this->tpl->set_tag("require_access", $privilege);
			$this->tpl->set_tag("require_access_title", $this->lang->format($privilege));
			
			echo $this->tpl->render('std/page');
			exit;
		}
	}
}

?>