<?php
namespace FF\ElasticaManager\Exception;

class ElasticaManagerProviderTransformException extends \Exception
{
	public function __construct($indexName)
	{
		$message = 'Index "'.$indexName.'" provider\'s iterationRowTransform() method must return array containing required keys: id, type, json';
		parent::__construct($message);
	}
}
