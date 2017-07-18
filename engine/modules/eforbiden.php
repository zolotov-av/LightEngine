<?php

/**
* Исключение отказа в доступе
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2009
*
* @package mod_session
*/
class EForbiden extends EViewable
{
	public function onRender($engine)
	{
		$engine->tpl->set_tag('CONTENT', 'std/forbiden');
		
		$form = $engine->post->newForm('login');
		$engine->tpl->set_tag('form', $form->render());
	}
}

?>