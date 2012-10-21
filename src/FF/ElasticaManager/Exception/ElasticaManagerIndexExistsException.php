<?php
namespace FF\ElasticaManager\Exception;

class ElasticaManagerIndexExistsException extends \Exception
{
	public function __construct($indexName)
	{
		$message = 'Index with name "'.$indexName.'" already exists';
		parent::__construct($message);
	}
}
