<?php

/**
* Класс простой веб-формы
*
* На базе старого обработчика форм wscore 1.x
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2009
*/
class PostForm extends LightComponent
{
	/**
	* Менеджер форм
	*/
	private $manager;
	
	/**
	* URL формы
	*/
	private $url;
	
	/**
	* Действие
	*/
	private $action;
	
	/**
	* Путь к файлу формы
	*/
	private $path;
	
	/**
	* Тип проверки формы
	*/
	private $check;
	
	/**
	* Описание формы
	*/
	private $data = array ();
	
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
	public function __construct(mod_post $manager, $action, $path, $check = false)
	{
		parent::__construct($manager->getEngine());
		$this->manager = $manager;
		$this->action = $action;
		$this->path = $path;
		$this->check = $check;
		$this->errors = array ();
		$this->values = array ();
		$this->data = parse_ini_file($path, true);
		$this->url = $this->config->read('site_prefix', '/') . "{$this->action}.php";
		if ( isset($this->data['__FORM__']) )
		{
			$this->info = $this->data['__FORM__'];
			unset($this->data['__FORM__']);
		}
		else $this->info = array ();
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
	* Вернуть идентификатор формы
	* @retval string идентификатор формы
	*/
	public function getAction()
	{
		return $this->action;
	}
	
	/**
	* Вернуть URL сценария обрабатывающего форму
	* @retval string URL сценария обрабатывающего форму
	*/
	public function getURL()
	{
		return $this->url;
	}
	
	/**
	* Установить URL сценария обрабатывающего форму
	* @param string URL сценария обрабатывающего форму
	*/
	public function setURL($url)
	{
		$this->url = $url;
	}
	
	/**
	* Вернуть заголовок формы
	* @retval string имя шаблона формы
	*/
	public function getTitle()
	{
		return isset($this->info['title']) ?
			$this->lang->format($this->info['title']) : $this->action;
	}
	
	/**
	* Вернуть шаблон формы
	* @retval string имя шаблона формы
	*/
	public function getTemplate()
	{
		return isset($this->info['template']) ?
			$this->info['template'] : 'std/form';
	}
	
	/**
	* Вернуть разметку формы
	* @retval string имя шаблона с разметкой формы
	*/
	public function getLayout()
	{
		return isset($this->info['layout']) ?
			$this->info['layout'] : 'std/formlayout';
	}
	
	/**
	* Вернуть разметку формы
	* @param string имя шаблона с разметкой формы
	*/
	public function setLayout($layout)
	{
		$this->info['layout'] = $layout;
	}
	
	/**
	* Вернуть тип параметра
	* @param string имя параметра
	* @retval string тип параметра или FALSE если такого параметра нет
	* @note если тип параметра не указан, то считается string
	*/
	public function getParamType($param)
	{
		if ( ! isset($this->data[$param]) ) return false;
		return isset($this->data[$param]['type'])
			? $this->data[$param]['type']
			: 'string';
	}
	
	/**
	* Вернуть заголовок параметра
	* @param string имя параметра
	* @retval string заголовок параметра или FALSE если такого параметра нет
	*/
	protected function getParamTitle($param)
	{
		if ( ! isset($this->data[$param]) ) return false;
		if ( isset($this->data[$param]['title']) )
		{
			return $this->lang->format($this->data[$param]['title']);
		}
		return $param;
	}
	
	/**
	* Вернуть виджет параметра
	* @param string имя параметра
	* @retval string имя виджета или FALSE если такого параметра нет
	*/
	protected function getParamWidget($param)
	{
		if ( ! isset($this->data[$param]) ) return false;
		if ( isset($this->data[$param]['widget']) )
		{
			return $this->data[$param]['widget'];
		}
		
		switch ( $this->getParamType($param) )
		{
		case 'integer': return 'text';
		case 'float': return 'text';
		case 'string': return 'text';
		case 'boolean': return 'boolean';
		case 'file': return 'file';
		case 'image': return 'file';
		case 'integer[integer]': return 'sort';
		case 'submit': return 'submit';
		default: throw new Exception("wrong type: " . $this->getParamType($param));
		}
	}
	
	/**
	* Проверить является ли параметр обязательным
	* @param string имя параметра
	* @retval bool TRUE - параметр опциональный, FALSE - параметр обязательный
	*/
	protected function isOptionalParam($param)
	{
		if ( isset($this->data[$param]) )
		{
			if ( isset($this->data[$param]['optional']) && $this->data[$param]['optional'] )
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	* Вернуть опции параметра
	* @param string имя параметра
	* @retval array опции параметра в виде ассоциативного массива
	*/
	protected function getParamOptions($param)
	{
		if ( isset($this->data[$param]) )
		{
			return isset($this->options[$param]) ? $this->options[$param] : array ();
		}
		return false;
	}
	
	/**
	* Загрузить значения по умолчанию
	*/
	public function loadDefaults()
	{
		foreach($this->data as $param => $inf)
		{
			$this->values[$param] = isset($inf['default']) ? $inf['default'] : NULL;
		}
	}
	
	/**
	* Обработка целочисленого параметра
	* @param string имя параметра
	* @retval integer значение параметра
	*/
	protected function parseInteger($param)
	{
		if ( ! isset($_POST[$param]) || trim($_POST[$param]) === '' )
		{
			if ( $this->isOptionalParam($param) ) return false;
			$this->errors[] = $this->lang->format('wscore:field_not_found', $this->getParamTitle($param));
			return 0;
		}
		$value = intval(trim($_POST[$param]));
		if ( isset($this->data[$param]['min-value']) )
		{
			$minval = intval($this->data[$param]['min-value']);
			if ( $value < $minval ) $this->errors[] = $this->lang->format('wscore:field_too_small', $this->getParamTitle($param), $minval, $value);
		}
		if ( isset($this->data[$param]['max-value']) )
		{
			$maxval = intval($this->data[$param]['max-value']);
			if ( $value > $maxval ) $this->errors[] = $this->lang->format('wscore:field_too_big', $this->getParamTitle($param), $maxval, $value);
		}
		return $value;
	}
	
	/**
	* Обработка вещественного параметра
	* @param string имя параметра
	* @retval floatval значение параметра
	*/
	protected function parseFloat($param)
	{
		if ( ! isset($_POST[$param]) || trim($_POST[$param]) === '' )
		{
			if ( $this->isOptionalParam($param) ) return false;
			$this->errors[] = $this->lang->format('wscore:field_not_found', $this->getParamTitle($param));
			return 0;
		}
		$value = floatval(trim($_POST[$param]));
		if ( isset($this->data[$param]['min-value']) )
		{
			$minval = floatval($this->data[$param]['min-value']);
			if ( $value < $minval ) $this->errors[] = $this->lang->format('wscore:field_too_small', $this->getParamTitle($param), $minval, $value);
		}
		if ( isset($this->data[$param]['max-value']) )
		{
			$maxval = floatval($this->data[$param]['max-value']);
			if ( $value > $maxval ) $this->errors[] = $this->lang->format('wscore:field_too_big', $this->getParamTitle($param), $maxval, $value);
		}
		return $value;
	}
	
	/**
	* Обработка строкового поля
	* @param string имя параметра
	* @retval string значение параметра
	*/
	protected function parseString($param)
	{
		if ( ! isset($_POST[$param]) )
		{
			if ( $this->isOptionalParam($param) ) return false;
			$this->errors[] = $this->lang->format('wscore:field_not_found', $this->getParamTitle($param));
			return '';
		}
		$value = trim($_POST[$param]);
		if ( $this->isOptionalParam($param) && $value === '' ) return false;
		$length = mb_strlen($value);
		if ( isset($this->data[$param]['min-length']) )
		{
			$minlen = intval($this->data[$param]['min-length']);
			if ( $length < $minlen ) $this->errors[] = $this->lang->format('wscore:field_too_short', $this->getParamTitle($param), $minlen, $length);
		}
		if ( isset($this->data[$param]['max-length']) )
		{
			$maxlen = intval($this->data[$param]['max-length']);
			if ( $length > $maxlen ) $this->errors[] = $this->lang->format('wscore:field_too_long', $this->getParamTitle($param), $maxlen, $length);
		}
		return $value;
	}
	
	/**
	* Обработка логического поля
	* @param string имя параметра
	* @retval bool значение параметра
	*/
	protected function parseBoolean($param)
	{
		return isset($_POST[$param]) && $_POST[$param];
	}
	
	/**
	* Обработка поля типа File
	* @param string имя параметра
	* @retval array описание загруженного файла
	*/
	protected function parseFile($param)
	{
		if ( ! isset($_FILES[$param]) ) return false;
		
		$inf = $_FILES[$param];
		
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
		
		if ( isset($this->data[$param]['min-size']) )
		{
			$minsize = intval($this->data[$param]['min-size']);
			if ( $inf['size'] < $minsize ) $this->errors[] = $this->lang->format('wscore:upload_too_small', $this->getParamTitle($param), $inf['size'], $minsize);
		}
		
		if ( isset($this->data[$param]['max-size']) )
		{
			$maxsize = intval($this->data[$param]['max-size']);
			if ( $inf['size'] > $maxsize ) $this->errors[] = $this->lang->format('wscore:upload_too_big', $this->getParamTitle($param), $inf['size'], $maxsize);
		}
		
		return $inf;
	}
	
	/**
	* Обработка параметра типа Image
	* @param string имя параметра
	* @retval array описание загруженного файла
	*/
	protected function parseImage($param)
	{
		$val = $this->parseFile($param);
		if ( $val === false ) return false;
		if ( $val['error'] == UPLOAD_ERR_NO_FILE ) return false;
		list($val['width'], $val['height'], $val['mime']) = getimagesize($_FILES[$param]['tmp_name']);
		if ( $val['width'] == 0 || $val['height'] == 0 )
		{
			$this->errors[] = $this->lang->format('wscore:bad_image');
			return false;
		}
		if ( isset($this->data[$param]['min-width']) )
		{
			$minwidth = intval($this->data[$param]['min-width']);
			if ( $val['width'] < $minwidth ) $this->errors[] = $this->lang->format('wscore:image_width_too_small', $this->getParamTitle($param), $val['width'], $minwidth);
		}
		if ( isset($this->data[$param]['max-width']) )
		{
			$maxwidth = intval($this->data[$param]['max-width']);
			if ( $val['width'] > $maxwidth ) $this->errors[] = $this->lang->format('wscore:image_width_too_big', $this->getParamTitle($param), $val['width'], $maxwidth);
		}
		if ( isset($this->data[$param]['min-height']) )
		{
			$minheight = intval($this->data[$param]['min-height']);
			if ( $val['height'] < $minheight ) $this->errors[] = $this->lang->format('wscore:image_height_too_small', $this->getParamTitle($param), $val['height'], $minheight);
		}
		if ( isset($this->data[$param]['max-height']) )
		{
			$maxheight = intval($this->data[$param]['max-height']);
			if ( $val['height'] > $maxheight ) $this->errors[] = $this->lang->format('wscore:image_height_too_big', $this->getParamTitle($param), $val['height'], $maxheight);
		}
		return $val;
	}
	
	/**
	* Обработка параметра integer[integer]
	* @param string имя параметра
	* @retval array значение параметра
	*/
	protected function parseIntegerToInteger($param)
	{
		$result = array ();
		if ( isset($_POST[$param]) && is_array($_POST[$param]) )
		{
			foreach($_POST[$param] as $key => $value)
			{
				$result[ intval($key) ] = intval($value);
			}
		}
		return $result;
	}
	
	/**
	* Обработка параметра
	* @param string имя параметра
	* @retval mixed значение параметра
	*/
	protected function parseParam($param)
	{
		switch ( $this->getParamType($param) )
		{
		case 'integer': return $this->parseInteger($param);
		case 'float': return $this->parseFloat($param);
		case 'string': return $this->parseString($param);
		case 'boolean': return $this->parseBoolean($param);
		case 'file': return $this->parseFile($param);
		case 'image': return $this->parseImage($param);
		case 'integer[integer]': return $this->parseIntegerToInteger($param);
		case 'submit': return isset($_POST[$param]);
		default: throw new Exception("wrong type: " . $this->getParamType($param));
		}
	}
	
	/**
	* Парсинг формы
	*/
	public function parse()
	{
		foreach ($this->data as $param => $inf)
		{
			$this->values[$param] = $this->parseParam($param);
		}
		if ( $this->check === 'sid' )
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
			elseif ( ! isset($_POST['sid']) || $_POST['sid'] !== $this->session->getSessionId() )
			{
				$this->errors[] = $this->lang->format('std:unauthorized');
			}
		}
	}
	
	/**
	* Вернуть js-код проверки целого числа
	*/
	protected function getIntegerCheck($param)
	{
		$code = "";
		$title = $this->getParamTitle($param);
		if ( isset($this->data[$param]['optional']) && $this->data[$param]['optional'] )
		{
			if ( isset($this->data[$param]['min-value']) )
			{
				$minval = intval($this->data[$param]['min-value']);
				$error = addslashes($this->lang->format('wscore:js_field_too_small', $title, $minval));
				$code .= "if ( form.$param.value != '' && parseInt(form.$param.value) < $minval )\n  return WSCFormError(form.$param, '', '$error');\n";
			}
			if ( isset($this->data[$param]['max-value']) )
			{
				$maxval = intval($this->data[$param]['max-value']);
				$error = addslashes($this->lang->format('wscore:js_field_too_big', $title, $maxval));
				$code .= "if ( form.$param.value != '' && parseInt(form.$param.value) > $maxval )\n  return WSCFormError(form.$param, '', '$error');\n";
			}
		}
		else
		{
			if ( isset($this->data[$param]['min-value']) )
			{
				$minval = intval($this->data[$param]['min-value']);
				$error = addslashes($this->lang->format('wscore:js_field_too_small', $title, $minval));
				$code .= "if ( parseInt(form.$param.value) < $minval )\n  return WSCFormError(form.$param, '', '$error');\n";
			}
			if ( isset($this->data[$param]['max-value']) )
			{
				$maxval = intval($this->data[$param]['max-value']);
				$error = addslashes($this->lang->format('wscore:js_field_too_big', $title, $maxval));
				$code .= "if ( parseInt(form.$param.value) > $maxval )\n  return WSCFormError(form.$param, '', '$error');\n";
			}
		}
		return $code;
	}
	
	/**
	* Вернуть js-код проверки вещественного числа
	*/
	protected function getFloatCheck($param)
	{
		$code = "";
		$title = $this->getParamTitle($param);
		if ( isset($this->data[$param]['optional']) && $this->data[$param]['optional'] )
		{
			if ( isset($this->data[$param]['min-value']) )
			{
				$minval = floatval($this->data[$param]['min-value']);
				$error = addslashes($this->lang->format('wscore:js_field_too_small', $title, $minval));
				$code .= "if ( form.$param.value != '' && parseFloat(form.$param.value) < $minval )\n  return WSCFormError(form.$param, '', '$error');\n";
			}
			if ( isset($this->data[$param]['max-value']) )
			{
				$maxval = floatval($this->data[$param]['max-value']);
				$error = addslashes($this->lang->format('wscore:js_field_too_big', $title, $maxval));
				$code .= "if ( form.$param.value != '' && parseFloat(form.$param.value) > $maxval )\n  return WSCFormError(form.$param, '', '$error');\n";
			}
		}
		else
		{
			if ( isset($this->data[$param]['min-value']) )
			{
				$minval = floatval($this->data[$param]['min-value']);
				$error = addslashes($this->lang->format('wscore:js_field_too_small', $title, $minval));
				$code .= "if ( parseFloat(form.$param.value) < $minval )\n  return WSCFormError(form.$param, '', '$error');\n";
			}
			if ( isset($this->data[$param]['max-value']) )
			{
				$maxval = floatval($this->data[$param]['max-value']);
				$error = addslashes($this->lang->format('wscore:js_field_too_big', $title, $maxval));
				$code .= "if ( parseFloat(form.$param.value) > $maxval )\n  return WSCFormError(form.$param, '', '$error');\n";
			}
		}
		return $code;
	}
	
	/**
	* Вернуть js-код проверки строки
	*/
	protected function getStringCheck($param)
	{
		$code = "";
		$title = $this->getParamTitle($param);
		if ( isset($this->data[$param]['optional']) && $this->data[$param]['optional'] )
		{
			if ( isset($this->data[$param]['min-length']) )
			{
				$minlen = intval($this->data[$param]['min-length']);
				$error = addslashes($this->lang->format('wscore:js_field_too_short', $title, $minlen));
				$code .= "if ( form.$param.value != '' && form.$param.value.length < $minlen )\n  return WSCFormError(form.$param, '', '$error');\n";
			}
			if ( isset($this->data[$param]['max-length']) )
			{
				$maxlen = intval($this->data[$param]['max-length']);
				$error = addslashes($this->lang->format('wscore:js_field_too_long', $title, $maxlen));
				$code .= "if ( form.$param.value != '' && form.$param.value.length > $maxlen )\n  return WSCFormError(form.$param, '', '$error');\n";
			}
		}
		else
		{
			if ( isset($this->data[$param]['min-length']) )
			{
				$minlen = intval($this->data[$param]['min-length']);
				$error = addslashes($this->lang->format('wscore:js_field_too_short', $title, $minlen));
				$code .= "if ( form.$param.value.length < $minlen )\n  return WSCFormError(form.$param, '', '$error');\n";
			}
			if ( isset($this->data[$param]['max-length']) )
			{
				$maxlen = intval($this->data[$param]['max-length']);
				$error = addslashes($this->lang->format('wscore:js_field_too_long', $title, $maxlen));
				$code .= "if ( form.$param.value.length > $maxlen )\n  return WSCFormError(form.$param, '', '$error');\n";
			}
		}
		return $code;
	}
	
	/**
	* Вернуть js-код сортировки
	*/
	protected function getSortCheck($param)
	{
		return "sort_submit(form, '$param');\n";
	}
	
	/**
	* Вернуть тело js-функции проверки формы
	*/
	protected function getOnSubmitBody()
	{
		$body = "{\n";
		foreach ($this->data as $param => $inf)
		{
			$typename = $this->getParamType($param);
			switch ( $typename )
			{
			case 'integer':
				$body .= $this->getIntegerCheck($param);
				break;
			case 'float':
				$body .= $this->getFloatCheck($param);
				break;
			case 'string':
				$body .= $this->getStringCheck($param);
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
		$params = array ();
		$submits = array ();
		if ( $this->check === 'sid' )
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
		foreach ($this->data as $param => $inf)
		{
			if ( ! isset($inf['type']) || $inf['type'] !== 'submit' )
				$params[$param] = array (
					'name' => $param,
					'title' => $this->getParamTitle($param),
					'value' => $this->values[$param],
					'widget' => 'widgets/' . $this->getParamWidget($param),
					'options' => $this->getParamOptions($param),
					'optional' => $this->isOptionalParam($param)
				);
			else
				$submits[$param] = array (
					'name' => $param,
					'title' => $this->getParamTitle($param),
					'widget' => 'widgets/' . $this->getParamWidget($param)
				);
		}
		return array (
			'url' => $this->getURL(),
			'action' => $this->action,
			'title' => $this->getTitle(),
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