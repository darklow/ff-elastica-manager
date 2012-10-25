<?php
namespace ElasticaManager\Tests;

use Elastica_Query_MatchAll;
use Elastica_Filter_Type;
use Elastica_Query;
use FF\ElasticaManager\DataProviderDocument;
use FF\ElasticaManager\Iterator;
use ElasticaManager\Tests\Configuration\TestConfiguration;

class IteratorTest extends ElasticaManagerTestBase
{
	public function testConstruct()
	{
		$indexName    = TestConfiguration::NAME.'_iterator_test';
		$indexManager = $this->_getIndexManager($indexName);
		$index        = $indexManager->create(true);
		$iterator     = new Iterator($this->client, $index);
		$this->assertEquals($iterator->getClient(), $this->client);
		$this->assertEquals($iterator->getIndex(), $index);
		$indexManager->delete();
	}

	public function testIterate()
	{
		$indexName    = TestConfiguration::NAME.'_iterate_test';
		$indexManager = $this->_getIndexManager($indexName);
		$indexManager->populate(null, null, true);

		$iterator = $indexManager->getIterator();

		$test    = $this;
		$closure = function (DataProviderDocument $doc, $i, $total) use ($test) {
			if (!$i) {
				$test->assertEquals(4, $total);
			}
		};

		$query = new Elastica_Query(new Elastica_Query_MatchAll());
		$iterator->iterate($query, $closure);

		// Test type query and break in $closure
		$query->setFilter(new Elastica_Filter_Type('dvd'));
		$j       = 0;
		$closure = function (DataProviderDocument $doc, $i, $total) use ($test, &$j) {
			$test->assertEquals(2, $total);
			$j++;
			return true;
		};
		$iterator->iterate($query, $closure, 2);
		$this->assertEquals(1, $j);

		$indexManager->delete();
	}
}
