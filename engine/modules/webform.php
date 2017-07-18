<?php

/**
* Описание веб-формы
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2009
*/
class WebForm extends LightComponent
{
	/**
	* Менеджер форм
	*/
	private $manager;
	
	/**
	* Действие
	*/
	private $action;
	
	/**
	* Набор форм
	*/
	private $formset;
	
	/**
	* Описание формы
	*/
	private $formdef;
	
	/**
	* Значения параметров
	*/
	public $values = array ();
	
	/**
	* Значения списков
	*/
	public $options = array ();
	
	/**
	* Сообщения об ошибках
	*/
	public $errors = array ();
	
	/**
	* Конструктор формы
	* @param mod_forms
	* @param DOMXPath
	* @param DOMElement
	*/
	public function __construct(mod_forms $manager, DOMXPath $formset, DOMElement $formdef)
	{
		parent::__construct($manager->getEngine());
		$this->manager = $manager;
		$this->formset = $formset;
		$this->formdef = $formdef;
		$this->errors = array ();
		$this->values = array ();
		foreach($formset->query('/formset/@name') as $name)
		{
			$this->action = $name->nodeValue . '.' . $formdef->getAttribute('action');
		}
	}
	
	/**
	* Добавить сообщение об ошибке
	*/
	public function error($msgid /* args */)
	{
		$args = func_get_args();
		$this->errors[] = $this->lang->formatExt($msgid, $args, 1);
	}
	
	/**
	* Проверить есть ли ошибки
	*/
	public function hasErrors()
	{
		return count($this->errors) > 0;
	}
	
	/**
	* Вернуть менеджер форм
	*/
	public function getManager()
	{
		return $this->manager;
	}
	
	/**
	* Вернуть аплет формы
	* @retval string имя аплета формы
	*/
	public function getApplet()
	{
		return $this->formdef->getAttribute('applet');
	}
	
	/**
	* Вернуть URL сценария обрабатывающего форму
	* @retval string URL сценария обрабатывающего форму
	*/
	public function getURL()
	{
		return $this->formdef->getAttribute('url');
	}
	
	/**
	* Вернуть точку входа в обработчик формы
	* @retval string имя метода в аплете
	*/
	public function getEntry()
	{
		return $this->formdef->hasAttribute('entry') ?
			$this->formdef->getAttribute('entry') :
			$this->formdef->getAttribute('action');
	}
	
	/**
	* Вернуть шаблон формы
	* @retval string имя шаблона формы
	*/
	public function getTemplate()
	{
		return $this->formdef->hasAttribute('template') ?
			$this->formdef->getAttribute('template') : 'std/form';
	}
	
	/**
	* Вернуть разметку формы
	* @retval string имя шаблона с разметкой формы
	*/
	public function getLayout()
	{
		return $this->formdef->hasAttribute('layout') ?
			$this->formdef->getAttribute('layout') : 'std/formlayout';
	}
	
	/**
	* Вернуть заголовок параметра
	*/
	protected function getParamTitle($param)
	{
		if ( $param->hasAttribute('title') ) return $param->getAttribute('title');
		$typename = $param->getAttribute('type');
		$typedef = $this->manager->lookupType($typename);
		if ( $typedef->hasAttribute('title') ) return $this->lang->format( $typedef->getAttribute('title') );
		return $param->getAttribute('name');
	}
	
	/**
	* Вернуть виджет параметра
	*/
	protected function getParamWidget($param)
	{
		if ( $param->hasAttribute('widget') ) return $param->getAttribute('widget');
		$typename = $param->getAttribute('type');
		$typedef = $this->manager->lookupType($typename);
		if ( $typedef->hasAttribute('widget') ) return $typedef->getAttribute('widget');
		switch ( $basetype = $typedef->getAttribute('basetype') )
		{
		case 'integer': return 'text';
		case 'float': return 'text';
		case 'string': return 'text';
		case 'boolean': return 'checkbox';
		case 'file': return 'file';
		case 'image': return 'file';
		case 'integer[integer]': return 'sort';
		default: throw new Exception("wrong type: $basetype");
		}
	}
	
	/**
	* Проверить является ли параметр обязательным
	*/
	protected function isOptionalParam($param)
	{
		if ( $param->hasAttribute('optional') )
		{
			return $param->getAttribute('optional') == 'yes';
		}
		$typename = $param->getAttribute('type');
		$typedef = $this->manager->lookupType($typename);
		if ( $typedef->hasAttribute('optional') )
		{
			return $typedef->getAttribute('optional') == 'yes';
		}
		return false;
	}
	
	/**
	* Вернуть опции параметра
	*/
	protected function getParamOptions($param)
	{
		$name = $param->getAttribute('name');
		if ( ! isset($this->options[$name]) ) return array ();
		$result = array ();
		foreach($this->options[$name] as $value => $title)
		{
			$result[ $value ] = array (
			'value' => $value,
			'title' => $title
			);
		}
		return $result;
	}
	
	/**
	* Вернуть значение параметра по-умолчанию
	*/
	protected function getParamDefaultValue($param)
	{
		$typename = $param->getAttribute('type');
		$typedef = $this->manager->lookupType($typename);
		switch ( $basetype = $typedef->getAttribute('basetype') )
		{
		case 'integer': return intval($typedef->getAttribute('default'));
		case 'float': return floatval($typedef->getAttribute('default'));
		case 'string': return $typedef->getAttribute('default');
		case 'boolean': return $typedef->getAttribute('default') == 'on';
		case 'file': return false;
		case 'image': return false;
		case 'integer[integer]': return array ();
		default: throw new Exception("wrong type: $basetype");
		}
	}
	
	/**
	* Загрузить значения по умолчанию
	*/
	public function loadDefaults()
	{
		$params = $this->formset->query("param", $this->formdef);
		foreach ($params as $param)
		{
			$this->values[ $param->getAttribute('name') ] = $this->getParamDefaultValue($param);
		}
	}
	
	/**
	* Обработка целочисленого параметра
	*/
	protected function parseInteger($param, $typedef)
	{
		$name = $param->getAttribute('name');
		if ( ! isset($_POST[$name]) || trim($_POST[$name]) === '' )
		{
			if ( $this->isOptionalParam($param) )
			{
				return intval( $typedef->getAttribute('default') );
			}
			$this->errors[] = $this->lang->format('wscore:field_not_found', $name);
			return 0;
		}
		$value = intval(trim($_POST[$name]));
		if ( $typedef->hasAttribute('min-value') )
		{
			$minval = intval($typedef->getAttribute('min-value'));
			if ( $value < $minval ) $this->errors[] = $this->lang->format('wscore:field_too_small', $name, $minval, $value);
		}
		if ( $typedef->hasAttribute('max-value') )
		{
			$maxval = intval($typedef->getAttribute('max-value'));
			if ( $value > $maxval ) $this->errors[] = $this->lang->format('wscore:field_too_big', $name, $maxval, $value);
		}
		return $value;
	}
	
	/**
	* Обработка вещественного параметра
	*/
	protected function parseFloat($param, $typedef)
	{
		$name = $param->getAttribute('name');
		if ( ! isset($_POST[$name]) || trim($_POST[$name]) === '' )
		{
			if ( $this->isOptionalParam($param) )
			{
				return floatval( $typedef->getAttribute('default') );
			}
			$this->errors[] = $this->lang->format('wscore:field_not_found', $name);
			return 0;
		}
		$value = floatval(trim($_POST[$name]));
		if ( $typedef->hasAttribute('min-value') )
		{
			$minval = floatval($typedef->getAttribute('min-value'));
			if ( $value < $minval ) $this->errors[] = $this->lang->format('wscore:field_too_small', $name, $minval, $value);
		}
		if ( $typedef->hasAttribute('max-value') )
		{
			$maxval = floatval($typedef->getAttribute('max-value'));
			if ( $value > $maxval ) $this->errors[] = $this->lang->format('wscore:field_too_big', $name, $maxval, $value);
		}
		return $value;
	}
	
	/**
	* Обработка строкового поля
	*/
	protected function parseString($param, $typedef)
	{
		$name = $param->getAttribute('name');
		if ( ! isset($_POST[$name]) )
		{
			if ( $this->isOptionalParam($param) )
			{
				return $typedef->getAttribute('default');
			}
			$this->errors[] = $this->lang->format('wscore:field_not_found', $name);
			return '';
		}
		$value = trim($_POST[$name]);
		if ( $this->isOptionalParam($param) && $value === '' )
		{
			return $typedef->getAttribute('default');
		}
		$length = mb_strlen($value);
		if ( $typedef->hasAttribute('min-length') )
		{
			$minlen = intval($typedef->getAttribute('min-length'));
			if ( $length < $minlen ) $this->errors[] = $this->lang->format('wscore:field_too_short', $name, $minlen, $length);
		}
		if ( $typedef->hasAttribute('max-length') )
		{
			$maxlen = intval($typedef->getAttribute('max-length'));
			if ( $length > $maxlen ) $this->errors[] = $this->lang->format('wscore:field_too_long', $name, $maxlen, $length);
		}
		return $value;
	}
	
	/**
	* Обработка логического поля
	*/
	protected function parseBoolean($param, $typedef)
	{
		$name = $param->getAttribute('name');
		return isset($_POST[$name]) && $_POST[$name];
	}
	
	/**
	* Обработка поля типа File
	*/
	protected function parseFile($param, $typedef)
	{
		$name = $param->getAttribute('name');
		
		if ( ! isset($_FILES[$name]) ) return false;
		
		$inf = $_FILES[$name];
		
		switch ( $inf['error'] )
		{
		case UPLOAD_ERR_NO_FILE: return false;
		case UPLOAD_ERR_INI_SIZE:
			$this->errors[] = $this->lang->format('wscore:upload_err_ini_size');
			return false;
		case UPLOAD_ERR_PARTIAL:
			$this->errors[] = $this->lang->format('wscore:upload_err_partial');
			return false;
		}
		
		if ( $inf['error'] != UPLOAD_ERR_OK )
		{
			$this->errors[] = $this->lang->format('wscore:upload_fault');
			return false;
		}
		
		if ( $typedef->hasAttribute('min-size') )
		{
			$minsize = intval($typedef->getAttribute('min-size'));
			if ( $inf['size'] < $minsize ) $this->errors[] = $this->lang->format('wscore:', $name, $inf['size'], $minsize);
		}
		
		if ( $typedef->hasAttribute('max-size') )
		{
			$maxsize = intval($typedef->getAttribute('max-size'));
			if ( $inf['size'] > $maxsize ) $this->errors[] = $this->lang->format('wscore:', $name, $inf['size'], $maxsize);
		}
		
		return $inf;
	}
	
	/**
	* Обработка параметра типа Image
	*/
	protected function parseImage($param, $typedef)
	{
		$name = $param->getAttribute('name');
		$val = $this->parseFile($param, $typedef);
		if ( $val === false ) return false;
		if ( $val['error'] == UPLOAD_ERR_NO_FILE ) return false;
		list($val['width'], $val['height'], $val['mime']) = getimagesize($_FILES[$name]['tmp_name']);
		if ( $val['width'] == 0 || $val['height'] == 0 )
		{
			$this->errors[] = $this->lang->format('wscore:bad_image');
			return false;
		}
		if ( $typedef->hasAttribute('min-width') )
		{
			$minwidth = intval($typedef->getAttribute('min-width'));
			if ( $val['width'] < $minwidth ) $this->errors[] = $this->lang->format('wscore:image_width_too_small', $name, $val['width'], $minwidth);
		}
		if ( $typedef->hasAttribute('max-width') )
		{
			$maxwidth = intval($typedef->getAttribute('max-width'));
			if ( $val['width'] > $maxwidth ) $this->errors[] = $this->lang->format('wscore:image_width_too_big', $name, $val['width'], $maxwidth);
		}
		if ( $typedef->hasAttribute('min-height') )
		{
			$minheight = intval($typedef->getAttribute('min-height'));
			if ( $val['height'] < $minheight ) $this->errors[] = $this->lang->format('wscore:image_height_too_small', $name, $val['height'], $minheight);
		}
		if ( $typedef->hasAttribute('max-height') )
		{
			$maxheight = intval($typedef->getAttribute('max-height'));
			if ( $val['height'] > $maxheight ) $this->errors[] = $this->lang->format('', $name, $val['height'], $maxheight);
		}
		return $val;
	}
	
	/**
	* Обработка параметра integer[integer]
	*/
	protected function parseIntegerToInteger($param, $typedef)
	{
		$name = $param->getAttribute('name');
		$result = array ();
		if ( isset($_POST[$name]) && is_array($_POST[$name]) )
		{
			foreach($_POST[$name] as $key => $value)
			{
				$result[ intval($key) ] = intval($value);
			}
		}
		return $result;
	}
	
	/**
	* Обработка параметра
	*/
	protected function parseParam($param)
	{
		$typename = $param->getAttribute('type');
		$typedef = $this->manager->lookupType($typename);
		switch ( $basetype = $typedef->getAttribute('basetype') )
		{
		case 'integer': return $this->parseInteger($param, $typedef);
		case 'float': return $this->parseFloat($param, $typedef);
		case 'string': return $this->parseString($param, $typedef);
		case 'boolean': return $this->parseBoolean($param, $typedef);
		case 'file': return $this->parseFile($param, $typedef);
		case 'image': return $this->parseImage($param, $typedef);
		case 'integer[integer]': return $this->parseIntegerToInteger($param, $typedef);
		default: throw new Exception("wrong type: $basetype");
		}
	}
	
	/**
	* Парсинг формы
	*/
	public function parse()
	{
		$params = $this->formset->query("param", $this->formdef);
		foreach ($params as $param)
		{
			$this->values[ $param->getAttribute('name') ] = $this->parseParam($param);
		}
		if ( $this->formdef->getAttribute('check') === 'sid' )
		{
			if ( isset($_POST['login']) && isset($_POST['password']) )
			{
				$login = $_POST['login'];
				$password = $_POST['password'];
				$user = $this->db->selectOne('users', '*', 'user_login = ' . $this->db->quote($login));
				$authOk = true;
				if ( $user === false )
				{
					$this->errors[] = $this->lang->format('std:user_not_found');
					$authOk = false;
				}
				elseif ( intval($user['user_status']) === 4 )
				{
					$this->errors[] = $this->lang->format('std:user_inactive');
					$authOk = false;
				}
				elseif ( md5($password) !== $user['user_passwd'] )
				{	$this->errors[] = $this->lang->format('std:login_fault');
					$authOk = false;
				}
				if ( $authOk ) {
					$this->session->start($user, false);
					if ( isset($_POST['autologin']) && $_POST['autologin'] )
					{
						$this->session->setAutologinCookie();
					}
				}
			}
			elseif ( ! isset($_POST['sid']) || $_POST['sid'] !== wscore_session_id() )
			{
				$this->errors[] = $this->lang->format('std:unauthorized');
			}
		}
	}
	
	/**
	* Вернуть js-код проверки целого числа
	*/
	protected function getIntegerCheck($param, $typedef)
	{
		$code = "";
		$arg = $param->getAttribute('name');
		$title = $this->getParamTitle($param);
		if ( $param->hasAttribute('optional') && $param->getAttribute('optional') == 'yes' )
		{
			if ( $typedef->hasAttribute('min-value') )
			{
				$minval = intval($typedef->getAttribute('min-value'));
				$error = addslashes($this->lang->format('wscore:js_field_too_small', $title, $minval));
				$code .= "if ( form.$arg.value != '' && parseInt(form.$arg.value) < $minval )\n  return WSCFormError(form.$arg, '', '$error');\n";
			}
			if ( $typedef->hasAttribute('max-value') )
			{
				$maxval = intval($typedef->getAttribute('max-value'));
				$error = addslashes($this->lang->format('wscore:js_field_too_big', $title, $maxval));
				$code .= "if ( form.$arg.value != '' && parseInt(form.$arg.value) > $maxval )\n  return WSCFormError(form.$arg, '', '$error');\n";
			}
		}
		else
		{
			if ( $typedef->hasAttribute('min-value') )
			{
				$minval = intval($typedef->getAttribute('min-value'));
				$error = addslashes($this->lang->format('wscore:js_field_too_small', $title, $minval));
				$code .= "if ( parseInt(form.$arg.value) < $minval )\n  return WSCFormError(form.$arg, '', '$error');\n";
			}
			if ( $typedef->hasAttribute('max-value') )
			{
				$maxval = intval($typedef->getAttribute('max-value'));
				$error = addslashes($this->lang->format('wscore:js_field_too_big', $title, $maxval));
				$code .= "if ( parseInt(form.$arg.value) > $maxval )\n  return WSCFormError(form.$arg, '', '$error');\n";
			}
		}
		return $code;
	}
	
	/**
	* Вернуть js-код проверки вещественного числа
	*/
	protected function getFloatCheck($param, $typedef)
	{
		$code = "";
		$arg = $param->getAttribute('name');
		$title = $this->getParamTitle($param);
		if ( $param->hasAttribute('optional') && $param->getAttribute('optional') == 'yes' )
		{
			if ( $typedef->hasAttribute('min-value') )
			{
				$minval = floatval($typedef->getAttribute('min-value'));
				$error = addslashes($this->lang->format('wscore:js_field_too_small', $title, $minval));
				$code .= "if ( form.$arg.value != '' && parseFloat(form.$arg.value) < $minval )\n  return WSCFormError(form.$arg, '', '$error');\n";
			}
			if ( $typedef->hasAttribute('max-value') )
			{
				$maxval = floatval($typedef->getAttribute('max-value'));
				$error = addslashes($this->lang->format('wscore:js_field_too_big', $title, $maxval));
				$code .= "if ( form.$arg.value != '' && parseFloat(form.$arg.value) > $maxval )\n  return WSCFormError(form.$arg, '', '$error');\n";
			}
		}
		else
		{
			if ( $typedef->hasAttribute('min-value') )
			{
				$minval = floatval($typedef->getAttribute('min-value'));
				$error = addslashes($this->lang->format('wscore:js_field_too_small', $title, $minval));
				$code .= "if ( parseFloat(form.$arg.value) < $minval )\n  return WSCFormError(form.$arg, '', '$error');\n";
			}
			if ( $typedef->hasAttribute('max-value') )
			{
				$maxval = floatval($typedef->getAttribute('max-value'));
				$error = addslashes($this->lang->format('wscore:js_field_too_big', $title, $maxval));
				$code .= "if ( parseFloat(form.$arg.value) > $maxval )\n  return WSCFormError(form.$arg, '', '$error');\n";
			}
		}
		return $code;
	}
	
	/**
	* Вернуть js-код проверки строки
	*/
	protected function getStringCheck($param, $typedef)
	{
		$code = "";
		$arg = $param->getAttribute('name');
		$title = $this->getParamTitle($param);
		if ( $param->hasAttribute('optional') && $param->getAttribute('optional') == 'yes' )
		{
			if ( $typedef->hasAttribute('min-length') )
			{
				$minlen = intval($typedef->getAttribute('min-length'));
				$error = addslashes($this->lang->format('wscore:js_field_too_short', $title, $minlen));
				$code .= "if ( form.$arg.value != '' && form.$arg.value.length < $minlen )\n  return WSCFormError(form.$arg, '', '$error');\n";
			}
			if ( $typedef->hasAttribute('max-length') )
			{
				$maxlen = intval($typedef->getAttribute('max-length'));
				$error = addslashes($this->lang->format('wscore:js_field_too_long', $title, $maxlen));
				$code .= "if ( form.$arg.value != '' && form.$arg.value.length > $maxlen )\n  return WSCFormError(form.$arg, '', '$error');\n";
			}
		}
		else
		{
			if ( $typedef->hasAttribute('min-length') )
			{
				$minlen = intval($typedef->getAttribute('min-length'));
				$error = addslashes($this->lang->format('wscore:js_field_too_short', $title, $minlen));
				$code .= "if ( form.$arg.value.length < $minlen )\n  return WSCFormError(form.$arg, '', '$error');\n";
			}
			if ( $typedef->hasAttribute('max-length') )
			{
				$maxlen = intval($typedef->getAttribute('max-length'));
				$error = addslashes($this->lang->format('wscore:js_field_too_long', $title, $maxlen));
				$code .= "if ( form.$arg.value.length > $maxlen )\n  return WSCFormError(form.$arg, '', '$error');\n";
			}
		}
		return $code;
	}
	
	/**
	* Вернуть js-код сортировки
	*/
	protected function getSortCheck($param, $typedef)
	{
		$name = $param->getAttribute('name');
		return "sort_submit(form, '$name');\n";
	}
	
	/**
	* Вернуть тело js-функции проверки формы
	*/
	protected function getOnSubmitBody()
	{
		$body = "{\n";
		$params = $this->formset->query("param", $this->formdef);
		foreach ($params as $param)
		{
			$name = $param->getAttribute('name');
			$typename = $param->getAttribute('type');
			$type = $this->manager->lookupType($typename);
			switch ( $type->getAttribute('basetype') )
			{
			case 'integer':
				$body .= $this->getIntegerCheck($param, $type);
				break;
			case 'float':
				$body .= $this->getFloatCheck($param, $type);
				break;
			case 'string':
				$body .= $this->getStringCheck($param, $type);
				break;
			case 'integer[integer]':
				if ( $this->getParamWidget($param) == 'sort' ) {
					$body .= $this->getSortCheck($param, $type);
				}
				break;
			}
		}
		return $body . "return true;\n}\n";
	}
	
	/**
	* Вернуть тег описания формы
	*/
	public function makeFormTag()
	{
		$paramdefs = $this->formset->query("param", $this->formdef);
		$params = array ();
		if ( $this->formdef->getAttribute('check') === 'sid' )
		{
			if ( ! $this->session->authorized() )
			{
				$params['login'] = array (
				'name' => 'login',
				'title' => $this->lang->format('std:login'),
				'value' => isset($_POST['login']) ? $_POST['login'] : '',
				'widget' => 'widgets/text'
				);
				$params['password'] = array (
				'name' => 'password',
				'title' => $this->lang->format('std:password'),
				'value' => '',
				'widget' => 'widgets/password'
				);
				$params['autologin'] = array (
				'name' => 'autologin',
				'title' => $this->lang->format('std:autologin'),
				'value' => ! empty($_POST['autologin']),
				'widget' => 'widgets/boolean'
				);
			}
			$params['sid'] = array (
			'name' => 'sid',
			'title' => '',
			'value' => $this->session->getSessionId(),
			'widget' => 'widgets/hidden',
			'authorize' => ! $this->session->authorized()
			);
		}
		foreach ($paramdefs as $param)
		{
			$name = $param->getAttribute('name');
			$params[ $name ] = array (
				'name' => $name,
				'title' => $this->getParamTitle($param),
				'value' => $this->values[$name],
				'widget' => 'widgets/' . $this->getParamWidget($param),
				'options' => $this->getParamOptions($param)
				);
		}
		$submitdefs = $this->formset->query("submit", $this->formdef);
		$submits = array ();
		foreach ($submitdefs as $submit)
		{
			$name = $submit->getAttribute('name');
			$submits[ $name ] = array (
				'name' => $name,
				'title' => $this->lang->format($submit->getAttribute('title'))
				);
		}
		return array (
			'url' => $this->getURL(),
			'action' => $this->action,
			'title' => $this->lang->format($this->formdef->getAttribute('title')),
			'template' => $this->getTemplate(),
			'layout' => $this->getLayout(),
			'errors' => $this->errors,
			'params' => $params,
			'submits' => $submits,
			'onsubmit' => $this->getOnSubmitBody()
			);
	}
	
	/**
	* Вернуть HTML-код формы
	* @retval string HTML-код формы
	*/
	public function render()
	{
		$this->tpl->set_tag('form', $this->makeFormTag());
		return $this->tpl->render($this->getLayout());
	}
}

?>