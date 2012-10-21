<?php
namespace FF\ElasticaManager\Exception;

class ElasticaManagerIndexNotFoundException extends \Exception
{
	public function __construct($indexName)
	{
		$message = 'Index with name "'.$indexName.'" not found';
		parent::__construct($message);
	}
}
