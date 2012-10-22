<?php
namespace FF\ElasticaManager\Exception;

class ElasticaManagerProviderIteratorException extends \Exception
{
	public function __construct($indexName)
	{
		$message = 'Index "'.$indexName.'" provider\'s getData() method must return array or \Traversable result';
		parent::__construct($message);
	}
}
