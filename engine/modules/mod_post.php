<?php

/**
* Простой обработчик форм
*
* Простой обработчик форм на базе обработчика форм wscore 1.x
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2010
*/
class mod_post extends LightModule
{
	/**
	* Определения форм
	*/
	private $forms = array ();
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_post($engine);
	}
	
	/**
	* Открыть форму
	* @param string идентификатор формы
	* @retval PostForm форма
	*/
	public function open($action)
	{
		if ( preg_match('/^([A-Za-z_0-9]+(?:\\/[A-Za-z_0-9]+)*)$/', $action, $match) )
		{
			$path = makepath(DIR_FORMS, "$action.frm");
			if ( file_exists($path) && is_readable($path) )
			{
				return new PostForm($this, $action, $path);
			}
		}
		throw new Exception("form not found: $action");
	}
	
	/**
	* Парсинг формы
	* @param string идентификатор формы
	* @retval PostForm форма
	*/
	public function parse($action)
	{
		$form = $this->open($action);
		$form->parse();
		return $form;
	}
	
	/**
	* Создать новую форму
	* @param string идентификатор формы
	* @retval PostForm форма
	*/
	public function newForm($action)
	{
		$form = $this->open($action);
		$form->loadDefaults();
		return $form;
	}
}

?>