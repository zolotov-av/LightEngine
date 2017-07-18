<?php

/*****************************************************************************

  Пример работы с модулем mod_forms (обработчик форм)

  В LightEngine представлено два модуля обработчика форм mod_post и mod_forms.
  В данном примере представлен обработчик форм на базе mod_forms.

  mod_post берет описания форм из файлов *.xml в каталоге DIR_FORMS

 *****************************************************************************/

/**
* Подключаем двигало
*/
require dirname(__FILE__) . "/system.php";

// session->init() обычно вызывается из system.php
$engine->session->init("default");

try
{
	if ( ! isset($_POST['action']) )
	{
		$form = $engine->forms->newForm('document.create');
		echo $form->render();
	}
	else
	{
		$form = $engine->forms->parse('document.create');
		if ( $form->hasErrors() )
		{
			echo $form->render();
		}
		else
		{
			header('content-type: text/plain; charset=utf-8');
			print_r($form->values);
		}
	}
}
catch (Exception $e)
{
	$class = get_class($e);
	$message = $e->getMessage();
	echo "Error ($class): $message\n";
}
