<?php
namespace ElasticaManager\Tests;

use Elastica_Client;
use FF\ElasticaManager\IndexManager;
use ElasticaManager\Tests\Configuration\TestDataProvider;
use ElasticaManager\Tests\Configuration\TestConfiguration;
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
		$configuration = new TestConfiguration(new TestDataProvider());
		$elasticaManager->addConfiguration($configuration);
		$configName = TestConfiguration::NAME;

		$this->assertEquals($configuration, $elasticaManager->getConfiguration($configName));
	}

	public function testGetIndexManager()
	{
		$configuration = new TestConfiguration(new TestDataProvider());
		$configName    = TestConfiguration::NAME;

		$indexManager = new IndexManager($this->client, $configuration, $configName);
		$this->assertEquals($indexManager, $this->elasticaManager->getIndexManager($configName));

		$this->assertEquals($indexManager, $this->indexManager);
		$this->assertEquals($this->elasticaManager, $this->elasticaManager);
	}

	public function testGetIndexManagerByWrongName()
	{
		$this->setExpectedException('InvalidArgumentException');
		$this->elasticaManager->getIndexManager('eim_wrong_name_test');
	}
}
