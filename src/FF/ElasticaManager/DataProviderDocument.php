<?php
namespace FF\ElasticaManager;

class DataProviderDocument
{
	protected $id;

	protected $typeName;

	protected $data;
	
	protected $fields;

	/**
	 * @param $id mixed Document ID
	 * @param $typeName string Document type name
	 * @param $data array Document source array
	 */
	function __construct($id, $typeName, array $data, array $fields = array())
	{
		$this->id       = $id;
		$this->typeName = $typeName;
		$this->data     = $data;
		$this->fields   = $fields;
	}

	/**
	 * Document ID
	 *
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Document type name
	 *
	 * @return string
	 */
	public function getTypeName()
	{
		return $this->typeName;
	}

	/**
	 * Document source array
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}
	
	/**
	 * Document fields array
	 *
	 * @return array
	 */
	public function getFields()
	{
		return $this->fields;
	}
}
