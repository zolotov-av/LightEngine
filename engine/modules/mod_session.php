<?php

/**
* Модуль менеджера сессий
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2009
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2016
*/
class mod_session extends LightModule
{
	/**
	* Информация о сессии
	* @var mixed
	*/
	protected $info = false;
	
	/**
	* Конструктор модуля
	*
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_session($engine);
	}
	
	/**
	* Вернуть информацию об IP пользователя
	* @param string информация об IP пользователя
	*/
	public static function getUserIpInfo()
	{
		$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		return isset($_SERVER['HTTP_X_FORWARDED_FOR'])
			? "$ip: $_SERVER[HTTP_X_FORWARDED_FOR]"
			: $ip;
	}
	
	/**
	* Вернуть ID текущей сессии
	* @retval mixed ID сессии или FALSE если нет сессии
	*/
	public function getSessionId()
	{
		return $this->info ? $this->info['session_id'] : false;
	}
	
	/**
	* Вернуть код сессии для формы
	* @retval string FormID или '' если нет сессии
	*/
	public function getFormId()
	{
		return $this->info ? $this->info['session_form_sid'] : '';
	}
	
	/**
	* Проверить session_id на корректность
	* @param string session_id
	* @retval string session_id если корректный и '' если некорректный
	*/
	protected function checkSessionId($value)
	{
		return preg_match('/^[a-fA-F0-9]{32,32}$/', $value) ? $value : '';
	}
	
	/**
	* Вернуть хеш автовохода
	* @retval string хеш автовохода
	*/
	protected function makeAutologinHash($user)
	{
		$uid = $user[ $this->config['uid_field'] ];
		$login = $user[ $this->config['login_field'] ];
		$password = $user[ $this->config['password_field'] ];
		return md5("$uid.$login.$password");
	}
	
	/**
	* Извлечь информацию о пользователе из autologin
	* @retval mixed информация об пользователе или FALSE в случае ошибки
	*/
	function getAutologin()
	{
		$autologin = unserialize($this->cgi->getCookie($this->config['autologin_cookie'], ''));
		if ( ! is_array($autologin) ) return false;
		if ( ! isset($autologin['id']) || ! isset($autologin['hash']) ) return false;
		if ( ! is_int($autologin['id']) || ! is_string($autologin['hash']) ) return false;
		$user = $this->db->selectOne($this->config['users_table'], '*', $this->config['uid_field'] . ' = ' . intval($autologin['id']));
		if ( $autologin['hash'] === $this->makeAutologinHash($user) ) return $user;
		return false;
	}
	
	/**
	* Выделить свободный код сессии
	* @retval string свободный код сессии
	*/
	protected function allocSessionId()
	{
		do
		{
			$session_id = sprintf("%06x%06x%04x%06x%06x%04x",
				mt_rand(0, 0xFFFFFF), mt_rand(0, 0xFFFFFF), mt_rand(0, 0xFFFF),
				mt_rand(0, 0xFFFFFF), mt_rand(0, 0xFFFFFF), mt_rand(0, 0xFFFF)
			);
		}
		while ( $this->db->countRows('sessions', 'session_id = ' . $this->db->quote($session_id)) > 0 );
		return $session_id;
	}
	
	/**
	* Записать код сессии в куки
	*/
	protected function setSessionCookie()
	{
		if ( $this->info )
		{
			$this->cgi->setCookie($this->config['session_cookie'], $this->info['session_id'], $this->config['session_lifetime']);
		}
		else
		{
			$this->cgi->removeCookie($this->config['session_cookie']);
		}
	}
	
	/**
	* Записать автовход в куки
	*/
	public function setAutologinCookie()
	{
		if ( $this->info )
		{
			$autologin = array (
			'id' => intval($this->info['user'][ $this->config['uid_field'] ]),
			'hash' => $this->makeAutologinHash($this->info['user'])
			);
			$this->cgi->setCookie($this->config['autologin_cookie'], serialize($autologin), $this->config['autologin_lifetime']);
		}
	}
	
	/**
	* Удалить куки автовхода
	*/
	function removeAutologinCookie()
	{
		$this->cgi->removeCookie($this->config['autologin_cookie']);
	}
	
	/**
	* Вернуть путь к файлу с конфигурацией
	*/
	protected function getConfigPath($domain)
	{
		$path = makepath(DIR_ROOT, 'config/session', "$domain.conf");
		if ( file_exists($path) ) return $path;
		return makepath(DIR_ROOT, 'config', 'mod_session.conf');
	}
	
	/**
	* Инициализация менеджера сессий
	*/
	public function init($domain='default')
	{
		$path = $this->getConfigPath($domain);
		$domains = parse_ini_file($path, true);
		$this->domain = $domain;
		$this->config = $domains[$domain];
		
		$expires = time() - $this->config['session_lifetime'];
		
		// удалить устаревшие сессии
		$this->db->delete('sessions', 'session_time < ' . $expires . ' AND session_domain = ' . $this->db->quote($this->domain));
		
		// найти открытую сессию
		$session_id = $this->checkSessionId($this->cgi->getCookie($this->config['session_cookie']));
		if ( $session_id )
		{
			if ( $this->info = $this->db->selectOne(array('sessions', $this->config['users_table']), '*', 'session_domain = ' . $this->db->quote($this->domain) . ' AND session_user_id = ' . $this->config['uid_field'] . ' AND session_id = ' . $this->db->quote($session_id) . ' AND session_time >= ' . $expires) )
			{
				$this->info['user'] = $this->info;
				$this->update();
				$this->setSessionCookie();
				// TODO: wscore_reload_user_config();
				return;
			}
		}
		
		// открытой сессии нет, проверить автовход
		if ( $user = $this->getAutologin() )
		{
			$this->start($user, true);
			$this->setSessionCookie();
			// TODO: wscore_reload_user_config();
			return;
		}
	}
	
	/**
	* Инициализация менеджера сессий с авторизацией HTTP Basic
	*/
	public function initHttpBasic($domain='default')
	{
		$path = $this->getConfigPath($domain);
		$domains = parse_ini_file($path, true);
		$this->domain = $domain;
		$this->config = $domains[$domain];
		$this->info = false;
		
		$realm = isset($this->config['realm']) ? $this->config['realm'] : $_SERVER['SERVER_NAME'];
		
		if ( isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER["PHP_AUTH_PW"]) )
		{
			$users_t = $this->config['users_table'];
			$login_f = $this->config['login_field'];
			$login_q = $this->db->quote($_SERVER["PHP_AUTH_USER"]);
			
			$user = $this->db->selectOne($users_t, '*', "$login_f = $login_q");
			if ( $user === false || strtolower(md5($_SERVER["PHP_AUTH_PW"])) !== strtolower($user['user_passwd']) )
			{
				header('WWW-Authenticate: Basic realm="' . $realm . '"');
				header('HTTP/1.0 401 Unauthorized');
				header('Content-type: text/plain');
				die("401 Unauthorized");
			}
			
			$this->info = $user;
			$this->info['user'] = $user;
		}
		else
		{
			header('WWW-Authenticate: Basic realm="' . $realm . '"');
			header('HTTP/1.0 401 Unauthorized');
			header('Content-type: text/plain');
			die("401 Unauthorized");
		}
	}
	
	/**
	* Инициализация менеджера сессий с авторизацией HTTP Basic
	*/
	public function httpAuthInit($user, $domain='default')
	{
		$path = $this->getConfigPath($domain);
		$domains = parse_ini_file($path, true);
		$this->domain = $domain;
		$this->config = $domains[$domain];
		$this->info = $user;
		if ( $user ) $this->info['user'] = $user;
	}
	
	/**
	* Обновить информацию о сессии в БД
	*/
	protected function update()
	{
		$time = time();
		$user_ips = $this->getUserIpInfo();
		$this->db->update('sessions', array(
		'session_time' => intval($time),
		'session_user_ips' => $this->db->quote($user_ips),
		), 'session_id = ' . $this->db->quote($this->info['session_id']));
		$this->info['session_time'] = $time;
		$this->info['session_user_ips'] = $user_ips;
	}
	
	/**
	* Начать новую сессию для пользователя
	*/
	public function start($user, $autologin)
	{
		$time = time();
		$user_ips = $this->getUserIpInfo();
		$session_id = $this->allocSessionId();
		$session_form_sid = $this->allocSessionId();
		$this->db->insert('sessions', array(
			'session_id' => $this->db->quote($session_id),
			'session_domain' => $this->db->quote($this->domain),
			'session_form_sid' => $this->db->quote($session_form_sid),
			'session_user_id' => intval($user[ $this->config['uid_field'] ]),
			'session_start' => $time,
			'session_time' => $time,
			'session_user_ips' => $this->db->quote($user_ips),
			'session_autologin' => intval($autologin)
		));
		$this->info = array (
		'session_id' => $session_id,
		'session_form_sid' => $session_form_sid,
		'user' => $user,
		'session_user_id' => intval($user[ $this->config['uid_field'] ]),
		'session_start' => $time,
		'session_time' => $time,
		'session_user_ips' => $user_ips,
		'session_autologin' => $autologin
		);
		// TODO: wscore_reload_user_config();
		$this->setSessionCookie();
		if ( $autologin ) $this->setAutologinCookie();
	}
	
	/**
	* Закрыть сессию
	*/
	public function close()
	{
		if ( $this->info )
		{
			// TODO: flush_user_config();
			$this->db->delete('sessions', 'session_id = ' . $this->db->quote($this->info['session_id']));
			$this->info = false;
			// удалить куки
			$this->setSessionCookie();
			$this->removeAutologinCookie();
		}
	}
	
	/**
	* Затребовать авторизацию
	* если пользователь уже авторизован, то вернуть информацию о пользователе
	* если пользователь не авторизован, то затребовать авторизацию
	*/
	public function authorize()
	{
		if ( $this->info ) return $this->info['user'];
		
		$this->tpl->set_tag('CONTENT', 'std/authorize');
		
		$form = $this->post->newForm($this->config['auth_form']);
		$form->values['return'] = $this->cgi->requestURL();
		$form->values['ticket'] = $this->ticket->sign(3600, $form->values['return']);
		$this->tpl->set_tag('form', $form->render());
		echo $this->tpl->render($this->config['auth_page']);
		exit;
	}
	
	/**
	* Проверить авторизован ли пользователь
	*/
	public function authorized()
	{
		return (bool) $this->info;
	}
	
	/**
	* Вернуть описание пользователя
	* @retval mixed описание пользователя или FALSE, если не авторизован
	*/
	public function getUserInfo()
	{
		if ( $this->info ) return $this->info['user'];
		return false;
	}
	
	/**
	* Вернуть описание сессии
	* @retval mixed описание сессии или FALSE, если не авторизован
	*/
	public function getSessionInfo()
	{
		if ( $this->info ) return $this->info;
		return false;
	}
}
