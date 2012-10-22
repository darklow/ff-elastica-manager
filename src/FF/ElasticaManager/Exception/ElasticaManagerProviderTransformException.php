<?php
namespace FF\ElasticaManager\Exception;

class ElasticaManagerProviderTransformException extends \Exception
{
	public function __construct($indexName)
	{
		$message = 'Index "'.$indexName.'" provider\'s iterationRowTransform() method must return DataProviderDocument object';
		parent::__construct($message);
	}
}
