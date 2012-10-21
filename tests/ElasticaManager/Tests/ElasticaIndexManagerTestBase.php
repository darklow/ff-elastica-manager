<?php
namespace ElasticaManager\Tests;

use Elastica_Client;
use FF\ElasticaManager\ElasticaIndexManager;
use ElasticaManager\Tests\Configuration\TestIndexDataProvider;
use ElasticaManager\Tests\Configuration\TestIndexConfiguration;

abstract class ElasticaIndexManagerTestBase extends \PHPUnit_Framework_TestCase
{
	/** @var Elastica_Client */
	protected $client;

	/** @var ElasticaIndexManager */
	protected $elasticaManager;

	protected function setUp()
	{
		$this->client  = new Elastica_Client($this->getClientConfig());
		$configuration = new TestIndexConfiguration();
		$provider      = new TestIndexDataProvider();

		$this->elasticaManager = new ElasticaIndexManager($this->client, $configuration, $provider);
	}

	protected function tearDown()
	{
		unset($this->client, $this->elasticaManager);
	}

	private function getClientConfig()
	{
		return array(
			'servers' => array(
				array(
					'host' => '192.168.0.223',
					'port' => 9200
				)
			)
		);
	}
}
