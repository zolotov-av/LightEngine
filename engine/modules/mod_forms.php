<?php

/**
* Менеджер форм
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2009
*/
class mod_forms extends LightModule
{
	/**
	* Определения форм
	*/
	private $formsets = array ();
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_forms($engine);
	}
	
	/**
	* Найти описание набора форм
	*/
	public function lookupFormSet($formset)
	{
		if ( ! isset($this->formsets[$formset]) )
		{
			$path = makepath(DIR_FORMS, str_replace('.', '/', $formset) . ".xml");
			if ( file_exists($path) )
			{
				$doc = DOMDocument::load($path);
				return $this->formsets[$formset] = new DOMXPath($doc);
			}
			return $this->formsets[$formset] = false;
		}
		return $this->formsets[$formset];
	}
	
	/**
	* Найти описание типа
	*/
	public function lookupType($typename)
	{
		if ( preg_match('/^([a-z_0-9]+(?:\\.[a-z_0-9]+)?)\\.([a-z_0-9]+)$/', $typename, $match) )
		{
			if ( $formset = $this->lookupFormSet($match[1]) )
			{
				$typedefs = $formset->query("/formset/typedef[@name='$match[2]']");
				foreach ($typedefs as $typedef) return $typedef;
			}
		}
		throw new Exception("wrong type name: $typename");
	}
	
	/**
	* Открыть форму
	*/
	public function open($action)
	{
		if ( preg_match('/^([a-z_0-9]+(?:\\.[a-z_0-9]+)?)\\.([a-z_0-9]+)$/', $action, $match) )
		{
			if ( $formset = $this->lookupFormSet($match[1]) )
			{
				$forms = $formset->query("/formset/form[@action='$match[2]']");
				foreach($forms as $formdef)
				{
					return new WebForm($this, $formset, $formdef);
				}
			}
		}
		throw new Exception("form not found: $action");
	}
	
	/**
	* Парсинг формы
	* @param string идентификатор действия
	* @retval WebForm форма
	*/
	public function parse($action)
	{
		$form = $this->open($action);
		$form->parse();
		return $form;
	}
	
	/**
	* Создать новую форму
	* @param string идентификатор действия
	* @retval WebForm форма
	*/
	public function newForm($action)
	{
		$form = $this->open($action);
		$form->loadDefaults();
		return $form;
	}
}

?>