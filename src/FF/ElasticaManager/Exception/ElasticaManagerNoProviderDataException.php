<?php
namespace FF\ElasticaManager\Exception;

class ElasticaManagerNoProviderDataException extends \Exception
{
	public function __construct($indexName)
	{
		$message = 'Index "'.$indexName.'" provider\'s getData() method returned null';
		parent::__construct($message);
	}
}
