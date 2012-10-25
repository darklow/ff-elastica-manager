<?php
namespace FF\ElasticaManager\Exception;

class ElasticaManagerNoAliasException extends \Exception
{
	public function __construct($indexName)
	{
		$message = 'Configuration "'.$indexName.'" doesn\'t have default alias configured';
		parent::__construct($message);
	}
}
