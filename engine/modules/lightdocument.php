<?php

/**
* Базовый класс документа
*
* (c) Zolotov Alex, 2009
*     zolotov-alex@shamangrad.net
*     http://shamangrad.net/docman.prj/
*/
class LightDocument extends LightComponent
{
	/**
	* Путь к документу
	*/
	private $documentPath;
	
	/**
	* Информация о документе
	*/
	private $documentInfo;
	
	/**
	* Конструктор документа
	* @param ModuleManager менеджер модулей
	* @param string путь к документу
	* @param array описание документа
	*/
	public function __construct(LightEngine $engine, $documentPath, array $documentInfo)
	{
		parent::__construct($engine);
		$this->documentPath = preg_replace('{/+}', '/', $documentPath);
		$this->documentInfo = $documentInfo;
	}
	
	/**
	* Вернуть ID документа
	* @retval int ID документа
	*/
	public function getDocumentId()
	{
		return intval($this->documentInfo['doc_id']);
	}
	
	/**
	* Возвращает описание класса
	*/
	public function getDocumentClass()
	{
		// TODO
		return null;
	}
	
	/**
	* Вернуть имя файла документа
	* @retval string имя файла документа
	*/
	public function getDocumentName()
	{
		return $this->documentInfo['doc_name'];
	}
	
	/**
	* Вернуть путь к документу
	* @retval string виртуальный путь к документу
	*/
	public function getDocumentPath()
	{
		return $this->documentPath;
	}
	
	/**
	* Вернуть URL к документу
	* @retval string URL к документу
	*/
	public function getDocumentURL()
	{
		return makeurl($this->documentPath);
	}
	
	/**
	* Вернуть заголовок документа
	* @retval string заголовок документа
	*/
	public function getDocumentTitle()
	{
		return $this->documentInfo['doc_title'];
	}
	
	/**
	* Вернуть контент документа (HTML)
	* @retval string контент документа (HTML)
	*/
	public function getDocumentContent()
	{
		return $this->documentInfo['doc_content'];
	}
	
	/**
	* Вернуть путь к каталогу с файлами прикрепленными к документу
	*/
	public function getDocumentFolder()
	{
		return makepath(MOD_DOC_DIR_DATA, intval($this->documentInfo['doc_id']));
	}
	
	/**
	* Вернуть URL к прикрепленному файлу
	*/
	public function getDocumentFile($fileName)
	{
		return makepath($this->config->read('site_prefix', ''), 'data', $this->getDocumentId(), $fileName);
	}
	
	/**
	* Прочитать параметр конфигурации документа
	*/
	public function readParam($name, $default = false)
	{
		$f = $this->db->selectOne('doc_params', '*', 'param_doc_id = ' . $this->getDocumentId() . ' AND param_name = ' . $this->db->quote($name));
		return $f ? $f['param_value'] : $default;
	}
	
	/**
	* Записать параметр в конфигурацию документа
	*/
	public function writeParam($name, $value)
	{
		$this->removeParam($name);
		$this->db->insert('doc_params', array (
		'param_doc_id' => $this->getDocumentId(),
		'param_name' => $this->db->quote($name),
		'param_value' => $this->db->quote($value)
		));
	}
	
	/**
	* Удалить параметр в конфигурации документа
	*/
	public function removeParam($name)
	{
		$this->db->delete('doc_params', 'param_doc_id = ' . $this->getDocumentId() . ' AND param_name = ' . $this->db->quote($name));
	}
	
	/**
	* Удалить все параметры документа
	*/
	public function removeAllParams()
	{
		$this->db->delete('doc_params', 'param_doc_id = ' . $this->getDocumentId());
	}
}

?>