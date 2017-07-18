<?php

/**
* Модуль управления правами доступа
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2010
*/

class mod_time extends LightModule
{
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_time($engine);
	}
	
	/**
	* Вернуть русское название месяца в родительном падеже
	*/
	public function rmonth($time)
	{
		static $months = array (
			'01' => 'января',
			'02' => 'февраля',
			'03' => 'марта',
			'04' => 'апреля',
			'05' => 'мая',
			'06' => 'июня',
			'07' => 'июля',
			'08' => 'августа',
			'09' => 'сентября',
			'10' => 'октября',
			'11' => 'ноября',
			'12' => 'декабря'
		);
		return $months[ date('m', $time) ];
	}
}

?>