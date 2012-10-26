<?php
namespace FF\ElasticaManager\Exception;

class ElasticaManagerIndexNotFoundException extends \Exception
{
	public function __construct($indexName, $byAlias = false)
	{
		$message = 'Index with'.($byAlias ? ' alias' : '').' name "'.$indexName.'" not found';
		parent::__construct($message);
	}
}
