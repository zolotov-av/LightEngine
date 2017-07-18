<?php

/**
* Компонент для работы с XSLT
*
* Зависит от расширений XSLTProcessor, DOM и iconv
*
* (c) Zolotov Alex, 2008-2010
*     zolotov-alex@shamangrad.net
*/

class mod_xslt
{
	/**
	* Кеш XSLT
	*/
	private static $cache = array ();
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_xslt($engine);
	}
	
	/**
	* Открыть XSLT-файл
	* @param string путь к файлу
	* @return подготовленный XSLTProcessor
	*/
	public static function lookup($fileName)
	{
		$path = realpath($fileName);
		if ( $path === false )
		{
			throw new Exception("XSLT not found: $fileName");
		}
		
		if ( isset(self::$cache[$path]) ) return self::$cache[$path];
		
		$xsl = new DOMDocument;
		if ( @ $xsl->load($path) === false )
		{
			throw new Exception("XSLT load fault: $name");
		}
		
		$proc = new XSLTProcessor;
		$proc->registerPHPFunctions();
		$proc->importStyleSheet($xsl);
		return self::$cache[$path] = $proc;
	}
	
	/**
	* Бубен 1: Закодировать текст
	*
	* Применить URL-кодирование для всех символов кроме
	* букв латинского алфавита, цифр и некоторых
	* символов разделителей.
	*/
	protected static function encode($text)
	{
		return preg_replace("/([^A-Za-z_0-9\\s\\-'\"&;<>\\/:=])/e", "'%' . bin2hex('\\1')", $text);
	}
	
	/**
	* Бубен 2: Раскодировать текст
	*
	* Функция обратная encode()
	*/
	protected static function decode($text)
	{
		return preg_replace("/%([A-Fa-f0-9]{2,2})/e", "chr(0x\\1)", $text);
	}
	
	/**
	* Танец с бубнами: преобразование HTML в XML
	*
	* @param string текст HTML-файла
	* @param string кодировака HTML-файла
	* @retval string XML-документ в кодировке UTF-8
	*/
	public static function html2xml($text, $encoding = 'UTF-8')
	{
		if ( strtoupper($encoding) !== 'UTF-8' )
		{
			$text = iconv($encoding, 'UTF-8', $text);
		}
		$doc = new DOMDocument();
		if ( @ $doc->loadHTML( self::encode($text) ) )
		{
			return self::decode( $doc->saveXML() );
		}
		return false;
	}
	
	/**
	* Преобразовать HTML-файл в XML с трансформацией структуры
	*
	* @param string текст HTML
	* @param string путь к XSLT
	* @param string кодировака HTML-файла (по-умолчанию UTF-8)
	* @retval DOMDocument XML-документ (объект DOMDocument) или NULL в случае ошибки
	*/
	public static function transformHTML2XML($html, $xslt, $encoding = 'UTF-8')
	{
		if ( $xml = self::html2xml($html, $encoding) )
		{
			$xsl = self::lookup($xslt);
			if ( $doc = DOMDocument::loadXML($xml) )
			{
				return $xsl->transformToDoc($doc);
			}
		}
		return null;
	}
	
	/**
	* Преобразовать HTML с трансформацией структуры
	*
	* @param string текст HTML
	* @param string путь к XSLT
	* @param string кодировака HTML-файла (по-умолчанию UTF-8)
	* @retval mixed преобразованный HTML или FALSE в случае ошибки
	*/
	public static function transformHTML($html, $xslt, $encoding = 'UTF-8')
	{
		if ( $xml = self::html2xml($html, $encoding) )
		{
			$xsl = self::lookup($xslt);
			if ( $doc = DOMDocument::loadXML($xml) )
			{
				return $xsl->transformToXML($doc);
			}
		}
		return false;
	}
	
	
	/**
	* Преобразовать XML-файл в XML с трансформацией структуры
	*
	* @param string текст XML
	* @param string путь к XSLT
	* @retval mixed текст преобразованного XML или FALSE в случае ошибки
	*/
	public static function transformXML($xml, $xslt)
	{
		if ( $xsl = self::lookup($xslt) )
		{
			$doc = new DOMDocument;
			if ( $doc->loadXML($xml) )
			{
				return $xsl->transformToXML($doc);
			}
		}
		return false;
	}
}

?>