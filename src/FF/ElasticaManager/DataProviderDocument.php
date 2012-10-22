<?php
namespace FF\ElasticaManager;

class DataProviderDocument
{
	protected $id;

	protected $typeName;

	protected $data;

	/**
	 * @param $id mixed Document ID
	 * @param $typeName string Document type name
	 * @param $data array Document source array
	 */
	function __construct($id, $typeName, array $data)
	{
		$this->id       = $id;
		$this->typeName = $typeName;
		$this->data     = $data;
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
}
