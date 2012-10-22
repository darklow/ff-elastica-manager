<?php
namespace ElasticaManager\Tests;

use Elastica_Client;
use FF\ElasticaManager\IndexManager;
use ElasticaManager\Tests\Configuration\TestIndexDataProvider;
use ElasticaManager\Tests\Configuration\TestIndexConfiguration;
use FF\ElasticaManager\ElasticaManager;

class ElasticaManagerTest extends ElasticaManagerTestBase
{
	public function testConstruct()
	{
		$elasticaManager = new ElasticaManager($this->client);
		$this->assertEquals($this->client, $elasticaManager->getClient());
	}

	public function testConfiguration()
	{
		$elasticaManager = new ElasticaManager($this->client);
		$configuration = new TestIndexConfiguration(new TestIndexDataProvider());
		$elasticaManager->addConfiguration($configuration);
		$configName = TestIndexConfiguration::NAME;

		$this->assertEquals($configuration, $elasticaManager->getConfiguration($configName));
	}

	public function testGetIndexManager()
	{
		$configuration = new TestIndexConfiguration(new TestIndexDataProvider());
		$configName    = TestIndexConfiguration::NAME;

		$indexManager = new IndexManager($this->client, $configuration, $configName);
		$this->assertEquals($indexManager, $this->elasticaManager->getIndexManager($configName));

		$this->assertEquals($indexManager, $this->indexManager);
		$this->assertEquals($this->elasticaManager, $this->elasticaManager);
	}
}
