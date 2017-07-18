<?php

/**
* Компонент для работы с Dr.Web AV-Desk Cabinet API
*
* Зависит от CURL, mod_curl
*
* (c) Zolotov Alex, 2010
*     zolotov-alex@shamangrad.net
*/

class mod_drwcab extends mod_curl
{
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_drwcab($engine);
	}
	
	/**
	* Вызов API-функции
	* @param string имя модуля
	* @param array аргументы
	*/
	public function execute($module, $args)
	{
		$this->authorize(
			$this->config->read('drweb_cabinet_authtype'),
			$this->config->read('drweb_cabinet_login'),
			$this->config->read('drweb_cabinet_password')
		);
		return $this->fetch( $this->config->read('drweb_cabinet_api') . $module . '?' . http_build_query($args) );
	}
	
	/**
	* Вернуть список пользователей
	* @retval string список пользователей в виде XML-файла
	*/
	public function getUserList()
	{
		return $this->execute('get_users_list.php', array('account' => 1));
	}
	
	/**
	* Вернуть описание пользователя
	* @param integer ID пользователя
	* @retval string описание пользователя в виде XML-файла
	*/
	public function getUserInfo($user_id)
	{
		return $this->execute('get_user_info.php', array (
			'id' => intval($user_id),
			'account' => 1
		));
	}
	
	/**
	* Регистрация пользователя
	* @param int ID клиента
	* @param string логин клиента
	* @param string e-mail клиента
	* @param string пароль клиента
	* @param string имя клиента
	* @param string фамилия клиента
	* @param string отчество клиента
	*/
	public function createUser($client_id, $login, $mail, $password, $name, $last_name, $patronymic)
	{
		return $this->execute('new_user.php', array(
			'login' => $login,
			'password' => $password,
			'email' => $mail,
			'name' => $name,
			'last_name' => $last_name,
			'patronymic' => $patronymic,
			'billing_id' => intval($client_id),
			'billing_login' => $login,
			'max_agents' => 10
		));
	}
	
	/**
	* Внести платеж
	* @param integer ID пользователя
	* @param float сумма платежа
	* @param string комментарий
	*/
	public function pay($user_id, $sum, $comment)
	{
		return $this->execute('user_balance.php', array (
			'id' => intval($user_id),
			'operation-code' => 2,
			'sum' => floatval($sum),
			'description' => $comment
		));
	}
	
	/**
	* Списать средства
	* @param integer ID пользователя
	* @param float сумма списываемых средств
	* @param string комментарий
	*/
	public function debet($user_id, $sum, $comment)
	{
		return $this->execute('user_balance.php', array (
			'id' => intval($user_id),
			'operation-code' => 1,
			'sum' => floatval($sum),
			'description' => $comment
		));
	}
}

?>