<?php
namespace ElasticaManager\Tests;

use Elastica_Client;
use Elastica_Index;
use FF\ElasticaManager\IndexManager;
use FF\ElasticaManager\ElasticaManager;
use ElasticaManager\Tests\Configuration\TestIndexDataProvider;
use ElasticaManager\Tests\Configuration\TestIndexConfiguration;

abstract class ElasticaManagerTestBase extends \PHPUnit_Framework_TestCase
{
	/** @var Elastica_Client */
	protected $client;

	/** @var ElasticaManager */
	protected $elasticaManager;

	/** @var IndexManager */
	protected $indexManager;

	protected function setUp()
	{
		$this->client  = new Elastica_Client($this->getClientConfig());
		$configuration = new TestIndexConfiguration(new TestIndexDataProvider());

		$this->elasticaManager = new ElasticaManager($this->client);
		$this->elasticaManager->addConfiguration($configuration);
		$this->indexManager = $this->elasticaManager->getIndexManager(TestIndexConfiguration::NAME);
	}

	protected function tearDown()
	{
		unset($this->client, $this->elasticaManager, $this->indexManager);
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

	/**
	 * @param string|null $indexName
	 * @return IndexManager
	 */
	protected function _getIndexManager($indexName = null)
	{
		$configuration = new TestIndexConfiguration(new TestIndexDataProvider());
		$indexName     = $indexName ? : $configuration->getName();
		return new IndexManager($this->client, $configuration, $indexName);
	}

	protected function _getTotalDocs(Elastica_Index $index)
	{
		$stats = $index->getStats()->getData();
		$count = $stats['_all']['primaries']['docs']['count'];
		return $count;
	}
}
