<?php

/**
* Компонент для скачивания объектов по HTTP/FTP
*
* Зависит от CURL
*
* (c) Zolotov Alex, 2008-2010
*     zolotov-alex@shamangrad.net
*/

class mod_curl extends LightModule
{
	/**
	* Заголовки HTTP-ответа в виде ассоциативного массива
	* @note имена заголовков в нижнем регистре
	*/
	public $headers;
	
	/**
	* Тип HTTP-авторизации
	*/
	protected $authorize = false;
	
	/**
	* Логин
	*/
	protected $auth_login;
	
	/**
	* Пассводр
	*/
	protected $auth_password;
	
	/**
	* Контент ответа
	*/
	protected $data;
	
	/**
	* Ограничение на размер файла в байтах или FASLE если без ограничения
	*/
	public $limit = false;
	
	/**
	* Сообщение об ошибке
	*/
	public $error = '';
	
	/**
	* Файловый дескриптор скачиваемого файла
	*/
	protected $fd;
	
	/**
	* Размер скачиваемого файла
	*/
	protected $fsize;
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_curl($engine);
	}
	
	/**
	* Принять один заголовок HTTP-ответа
	* @param resource CURL handler
	* @param string заголовов
	* @return int размер заголовка или -1, если нужно первать
	*/
	protected function accept_headers($ch, $header)
	{
		if ( preg_match('/^HTTP\\/1.\\d\\s+404/', $header) )
		{
			$this->error = "404 Page not found";
			return -1;
		}
		
		$i = strpos($header, ':');
		if ( $i !== false )
		{
			$name = strtolower(trim(substr($header, 0, $i)));
			$value = trim(substr($header, $i + 1));
			$this->headers[$name] = $value;
			
			// проверка ограничения на размер принимаемого файла
			if ( $this->limit !== false
				&& $name === 'content-length'
				&& $value > $this->limit )
			{
				$this->error = 'content too large';
				return -1;
			}
		}
		return strlen($header);
	}
	
	/**
	* Принять порцию данных
	* @param resource CURL handler
	* @param string порция данных
	* @return int размер приянтых данных или -1, если нужно первать
	*/
	protected function accept_data($ch, $chunk)
	{
		$args = func_get_args();
		$this->data .= $chunk;
		
		// проверка ограничения на размер принимаемого файла
		if ( $this->limit !== false && strlen($this->data) > $this->limit )
		{
			$this->error = 'content too large';
			return -1;
		}
		
		return strlen($chunk);
	}
	
	/**
	* Записать порцию данных в файл
	* @param resource CURL handler
	* @param string порция данных
	* @return int размер приянтых данных или -1, если нужно первать
	*/
	protected function accept_to_file($ch, $chunk)
	{
		$this->fsize += $chunk_len = strlen($chunk);
		
		// проверка ограничения на размер принимаемого файла
		if ( $this->limit !== false && $this->fsize > $this->limit )
		{
			$this->error = 'content to large';
			return -1;
		}
		
		$count = @ fwrite($this->fd, $chunk, $chunk_len);
		if ( $count === false || $count < $chunk_len )
		{
			$this->error = 'write file fault';
			return -1;
		}
		return $chunk_len;
	}
	
	/**
	* Авторизация
	*/
	public function authorize($type, $login, $password)
	{
		$this->authorize = $type;
		$this->auth_login = $login;
		$this->auth_password = $password;
	}
	
	/**
	* Скачать объект в строку
	*
	* @param string ссылка на объект
	* @return mixed контент объекта или FALSE в случае ошибки
	*/
	public function fetch($URL)
	{
		$this->headers = array ();
		$this->data = '';
		$this->error = '';
		
		$ch = curl_init();
		
		try
		{
			curl_setopt($ch, CURLOPT_URL, $URL);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_HEADERFUNCTION, array(& $this, 'accept_headers'));
			curl_setopt($ch, CURLOPT_WRITEFUNCTION, array(& $this, 'accept_data'));
			
			switch ( $this->authorize )
			{
			case 'basic':
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
				curl_setopt($ch, CURLOPT_USERPWD, "{$this->auth_login}:{$this->auth_password}");
				break;
			}
			
			$status = curl_exec($ch);
		}
		catch (Exception $e)
		{
			$status = false;
			$this->error = $e->getMessage();
		}
		
		if ( $status === false )
		{
			if ( empty($this->error) )
			{
				$this->error = curl_error($ch);
			}
			curl_close($ch);
			return false;
		}
		
		if ( isset($this->headers['content-length']) && strlen($this->data) < $this->headers['content-length'] )
		{
			$this->error = 'Ошибка загрузки';
			return false;
		}
		
		curl_close($ch);
		return $this->data;
	}
	
	/**
	* Скачать объект по методу POST в строку
	*
	* @param string ссылка на объект
	* @return mixed контент объекта или FALSE в случае ошибки
	*/
	public function fetchPost($URL, $args)
	{
		$this->headers = array ();
		$this->data = '';
		$this->error = '';

		$ch = curl_init();

		try
		{
			curl_setopt($ch, CURLOPT_URL, $URL);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_HEADERFUNCTION, array(& $this, 'accept_headers'));
			curl_setopt($ch, CURLOPT_WRITEFUNCTION, array(& $this, 'accept_data'));

			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

			switch ( $this->authorize )
			{
			case 'basic':
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
				curl_setopt($ch, CURLOPT_USERPWD, "{$this->auth_login}:{$this->auth_password}");
				break;
			}

			$status = curl_exec($ch);
		}
		catch (Exception $e)
		{
			$status = false;
			$this->error = $e->getMessage();
		}

		if ( $status === false )
		{
			if ( empty($this->error) )
			{
				$this->error = curl_error($ch);
			}
			curl_close($ch);
			return false;
		}

		if ( isset($this->headers['content-length']) && strlen($this->data) < $this->headers['content-length'] )
		{
			$this->error = 'Ошибка загрузки';
			return false;
		}

		curl_close($ch);
		return $this->data;
	}

	/**
	* Скачать объект в файл
	*
	* @param string ссылка на объект
	* @param string путь к файлу
	* @return bool TRUE - объект сохранён, FALSE - произошла ошибка
	*/
	public function fetchToFile($URL, $path)
	{
		$this->fd = @ fopen($path, "w");
		if ( $this->fd === false )
		{
			$this->error = "create file fault";
			return false;
		}
		
		$this->headers = array ();
		$this->fsize = 0;
		$this->error = '';
		
		$ch = curl_init();
		
		try
		{
			curl_setopt($ch, CURLOPT_URL, $URL);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_HEADERFUNCTION, array(& $this, 'accept_headers'));
			curl_setopt($ch, CURLOPT_WRITEFUNCTION, array(& $this, 'accept_to_file'));
			
			$status = curl_exec($ch);
		}
		catch (Exception $e)
		{
			$status = false;
			$this->error = $e->getMessage();
		}
		
		fclose($this->fd);
		
		if ( $status === false )
		{
			if ( empty($this->error) )
			{
				$this->error = curl_error($ch);
			}
			curl_close($ch);
			@ unlink($path);
			return false;
		}
		
		curl_close($ch);
		return true;
	}
}

?>
