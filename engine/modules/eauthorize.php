<?php

/**
* Исключение авторизации
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2009
*
* @package mod_session
*/
class EAuthorize extends EViewable
{
	public function onRender($engine)
	{
		$engine->tpl->set_tag('CONTENT', 'std/authorize');
		
		$form = $engine->post->newForm('login');
		$form->values['return'] = $engine->cgi->requestURL();
		$form->values['ticket'] = $engine->ticket->sign(3600, $form->values['return']);
		$engine->tpl->set_tag('form', $form->render());
	}
}

?>