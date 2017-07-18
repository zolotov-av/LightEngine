<?php

/**
* Отображаемое исключение
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2010
*/
abstract class EViewable extends Exception
{
	public function getLayout()
	{
		return "layout/default";
	}
	
	public function getPageTemplate()
	{
		return "std/page";
	}
	
	public abstract function onRender($engine);
	
	public function render($engine)
	{
		$engine->tpl->set_tag('LAYOUT', 'layout/default');
		$engine->tpl->open('INFO');
			$engine->tpl->set_tag('PREFIX', $engine->config->read('site_prefix', '/'));
			$engine->tpl->set_tag('SKINDIR', $engine->config->read('site_prefix', '/') . 'themes/default');
		$engine->tpl->close();
		
		$this->onRender($engine);
		
		return $engine->tpl->render($this->getPageTemplate());
	}
}

?>