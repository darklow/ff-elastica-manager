<?php
namespace ElasticaManager\Tests;

use Elastica_Client;
use FF\ElasticaManager\Exception\ElasticaManagerNoAliasException;
use FF\ElasticaManager\Configuration;
use Elastica_Type;
use Elastica_Index;
use ElasticaManager\Tests\Configuration\TestDataProvider;
use ElasticaManager\Tests\Configuration\TestConfiguration;
use FF\ElasticaManager\ElasticaManager;
use FF\ElasticaManager\IndexManager;

class IndexManagerTest extends ElasticaManagerTestBase
{
	public function testConstruct()
	{
		$configuration = new TestConfiguration(new TestDataProvider());
		$indexName     = $configuration->getName();
		$indexManager  = new IndexManager($this->client, $configuration, $indexName);
		$this->assertEquals($this->indexManager, $indexManager);
		$this->assertEquals($this->client, $indexManager->getClient());
		$this->assertEquals($configuration, $indexManager->getConfiguration());
		$this->assertEquals($indexName, $indexManager->getIndexName());

		// Test different name than default one
		$indexName    = $configuration->getName().'_special';
		$indexManager = new IndexManager($this->client, $configuration, $indexName);
		$this->assertEquals($indexName, $indexManager->getIndexName());
	}

	public function testCreateIndex()
	{
		$indexName = TestConfiguration::NAME;
		$index     = $this->indexManager->create(true);
		$newIndex  = new Elastica_Index($this->client, $indexName);
		$this->assertEquals($newIndex, $index);

		$this->indexManager->delete();
	}

	public function testCreateIndexIfExists()
	{
		$this->setExpectedException('FF\ElasticaManager\Exception\ElasticaManagerIndexExistsException');
		$this->indexManager->create();
		$this->indexManager->create();
	}

	/**
	 * @depends testCreateIndexIfExists
	 */
	public function testIndexExists()
	{
		$this->assertTrue($this->indexManager->indexExists());
	}

	/**
	 * @depends testIndexExists
	 */
	public function testDeleteIndex()
	{
		$response     = $this->indexManager->delete();
		$responseData = $response->getData();
		$this->assertTrue($responseData['ok']);
	}

	public function testCreateIndexDifferentName()
	{
		$indexName    = TestConfiguration::NAME.'_diff';
		$indexManager = $this->_getIndexManager($indexName);
		$indexManager->create(true);

		$this->assertEquals($indexName, $indexManager->getIndexName());
		$this->assertTrue($indexManager->indexExists($indexName));

		$indexManager->delete();
		$this->assertFalse($indexManager->indexExists());
	}

	public function testSetMapping()
	{
		$indexName    = TestConfiguration::NAME.'_mapping_test';
		$indexManager = $this->_getIndexManager($indexName);
		$index        = $indexManager->create(true);
		$mapping      = $index->getMapping();

		$configuration = $indexManager->getConfiguration();
		$types         = $configuration->getTypes();
		foreach ($types as $typeName) {
			$properties     = $mapping[$indexName][$typeName]['properties'];
			$confProperties = $configuration->getMappingProperties(new Elastica_Type($index, $typeName));
			ksort($properties);
			ksort($confProperties);
			$this->assertEquals(array_keys($properties), array_keys($confProperties));
		}

		$indexManager->delete();
	}

	public function testPopulateAll()
	{
		$indexName    = TestConfiguration::NAME.'_populate_test';
		$indexManager = $this->_getIndexManager($indexName);

		$test    = $this;
		$closure = function ($i, $total) use ($test) {
			$test->assertGreaterThanOrEqual(4, $total);
		};
		$index   = $indexManager->populate(null, $closure, true);
		$count   = $this->_getTotalDocs($index);
		$this->assertEquals(4, $count);

		$indexManager->delete();
	}

	public function testDefaultAlias()
	{
		$indexName    = TestConfiguration::NAME.'_alias_test';
		$indexManager = $this->_getIndexManager($indexName);
		$index        = $indexManager->create(true);

		$defaultAliasName = $indexManager->getConfiguration()->getAlias();
		$indexManager->addDefaultAlias();
		$aliases = $index->getStatus()->getAliases();
		$this->assertTrue(in_array($defaultAliasName, $aliases));

		$indexManager->removeDefaultAlias();
		$aliases = $index->getStatus()->getAliases();
		$this->assertFalse(in_array($defaultAliasName, $aliases));

		$indexManager->delete();
	}

	public function testAddDefaultAliasException()
	{
		$this->setExpectedException('FF\ElasticaManager\Exception\ElasticaManagerNoAliasException');

		$configuration = new TestEmptyConfiguration(new TestDataProvider());
		$this->elasticaManager->addConfiguration($configuration);
		$indexManager = $this->elasticaManager->getIndexManager($configuration::NAME);
		$indexManager->create(true);
		try {
			$indexManager->addDefaultAlias();
		}
		catch (ElasticaManagerNoAliasException $e) {
			$indexManager->delete();
			throw $e;
		}
	}
}

class TestEmptyConfiguration extends Configuration
{
	const NAME = 'eim_test_empty';

	public function getName()
	{
		return self::NAME;
	}

	public function getAlias()
	{
	}

	public function getTypes()
	{
		return array('empty');
	}

	public function getConfig()
	{
	}

	public function getMappingParams(Elastica_Type $type)
	{
	}

	public function getMappingProperties(Elastica_Type $type)
	{
	}
}
