<?php
namespace ElasticaManager\Tests;

use Elastica_Client;
use Elastica_Type;
use Elastica_Index;
use ElasticaManager\Tests\Configuration\TestIndexDataProvider;
use ElasticaManager\Tests\Configuration\TestIndexConfiguration;
use FF\ElasticaManager\ElasticaIndexManager;

class ElasticaIndexManagerTest extends ElasticaIndexManagerTestBase
{
	public function testElasticaIndexManagerConstruct()
	{
		$configuration   = new TestIndexConfiguration();
		$provider        = new TestIndexDataProvider();
		$elasticaManager = new ElasticaIndexManager($this->client, $configuration, $provider);

		$this->assertEquals($this->client, $elasticaManager->getClient());
		$this->assertEquals($provider, $elasticaManager->getProvider());
		$this->assertEquals($configuration, $elasticaManager->getConfiguration());

		$this->assertEquals($elasticaManager, $this->elasticaManager);
	}

	public function testSetIndexName()
	{
		$indexName = TestIndexConfiguration::NAME.'_diff';
		$this->elasticaManager->setIndexName($indexName);
		$this->assertEquals($indexName, $this->elasticaManager->getIndexName());
	}

	public function testCreateIndex()
	{
		$indexName = TestIndexConfiguration::NAME;
		$index     = $this->elasticaManager->createIndex();
		$newIndex  = new Elastica_Index($this->client, $indexName);
		$this->assertEquals($newIndex, $index);
	}

	/**
	 * @depends testCreateIndex
	 */
	public function testCreateIndexIfExists()
	{
		$this->setExpectedException('FF\ElasticaManager\Exception\ElasticaManagerIndexExistsException');

		$this->elasticaManager->createIndex();
	}

	/**
	 * @depends testCreateIndex
	 */
	public function testIndexExists()
	{
		$this->assertTrue($this->elasticaManager->indexExists());
	}

	/**
	 * @depends testIndexExists
	 */
	public function testDeleteIndex()
	{
		$response     = $this->elasticaManager->deleteIndex();
		$responseData = $response->getData();
		$this->assertTrue($responseData['ok']);
	}

	public function testCreateIndexDifferentName()
	{
		$indexName = TestIndexConfiguration::NAME.'_diff';
		$this->elasticaManager->setIndexName($indexName);

		$index    = $this->elasticaManager->createIndex();
		$newIndex = new Elastica_Index($this->client, $indexName);
		$this->assertEquals($newIndex, $index);
		$this->assertTrue($this->elasticaManager->indexExists($indexName));

		$this->elasticaManager->deleteIndex();
		$this->assertFalse($this->elasticaManager->indexExists());

		// Reset name back to default
		$this->elasticaManager->setIndexName(TestIndexConfiguration::NAME);
	}

	public function testSetMapping()
	{
		$indexName = TestIndexConfiguration::NAME.'_maptest';
		$this->elasticaManager->setIndexName($indexName);

		$index         = $this->elasticaManager->createIndex(true);
		$mapping       = $index->getMapping();
		$configuration = $this->elasticaManager->getConfiguration();
		$types         = $configuration->getTypes();
		foreach ($types as $typeName) {
			$properties     = $mapping[$indexName][$typeName]['properties'];
			$confProperties = $configuration->getMappingProperties(new Elastica_Type($index, $typeName));
			ksort($properties);
			ksort($confProperties);
			$this->assertEquals(array_keys($properties), array_keys($confProperties));
		}

		$this->elasticaManager->deleteIndex();
	}

	public function testPopulateAll()
	{
		$indexName = TestIndexConfiguration::NAME.'_poptest';
		$this->elasticaManager->setIndexName($indexName);
		$test = $this;
		$closure = function($i, $total) use ($test) {
			$test->assertGreaterThanOrEqual(4, $total);
		};
		$index = $this->elasticaManager->populate(null, $closure, true);
		$count = $this->getTotalDocs($index);
		$this->assertEquals(4, $count);
	}

	public function testPopulateOneType()
	{
		$indexName = TestIndexConfiguration::NAME.'_poptest';
		$this->elasticaManager->setIndexName($indexName);
		$test = $this;
		$closure = function($i, $total) use ($test) {
			$test->assertGreaterThanOrEqual(2, $total);
		};
		$index = $this->elasticaManager->populate('book', $closure);
		$count = $this->getTotalDocs($index);
		$this->assertEquals(2, $count);

		// Add two more documents, without droping index
		$index = $this->elasticaManager->populate('dvd', null, false);
		$count = $this->getTotalDocs($index);
		$this->assertEquals(4, $count);

	}

	protected function getTotalDocs(Elastica_Index $index)
	{
		$stats = $index->getStats()->getData();
		$count = $stats['_all']['primaries']['docs']['count'];
		return $count;
	}
}
