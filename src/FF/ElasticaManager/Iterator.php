<?php
namespace FF\ElasticaManager;

use Elastica\Client;
use Elastica\Document;
use Elastica\Query;
use Elastica\Query\AbstractQuery;
use Elastica\Index;

class Iterator
{
	/** @var Client */
	protected $client;

	/** @var Index */
	protected $index;

	function __construct(Client $client, Index $index)
	{
		$this->client = $client;
		$this->index  = $index;
	}

	/**
	 * @return Client
	 */
	public function getClient()
	{
		return $this->client;
	}

	/**
	 * @return Index
	 */
	public function getIndex()
	{
		return $this->index;
	}

	/**
	 * Iterate on index documents and perform $closure
	 * Iteration uses ElasticSearch scroll scan methods
	 * Note: Using setLimit(N) and setFrom(N) for query does not affect actual limit and offset (Limited by ES scan/scroll functionality, see Docs in link)
	 *
	 * See docs about $scroll in link:
	 * @link http://www.elasticsearch.org/guide/reference/api/search/scroll.html
	 *
	 * @param Query|AbstractQuery $query
	 * @param \Closure $closure Receives arguments: function(DataProviderDocument $doc, $i, $total); Return TRUE in $closure if you want to break and stop iteration
	 * @param int $batchSize
	 * @param string $scroll
	 */
	public function iterate(Query $query, \Closure $closure, $batchSize = 100, $scroll = '5m')
	{
		$response = $this->index->request('_search', 'GET', $query->toArray(), array(
			'search_type' => 'scan',
			'scroll'      => $scroll,
			'size'        => $batchSize,
			'limit'       => 1
		));

		$data     = $response->getData();
		$scrollId = $data['_scroll_id'];
		$total    = $data['hits']['total'];
		$i        = 0;

		$response = $this->client->request('_search/scroll', 'GET', $scrollId, array(
			'scroll' => $scroll,
		));

		$data = $response->getData();
		while (count($data['hits']['hits']) > 0) {
			foreach ($data['hits']['hits'] as $item) {
				$itemData = $item['_source'];
				$doc      = new DataProviderDocument($item['_id'], $item['_type'], $itemData);
				if ($break = $closure($doc, $i, $total)) {
					break 2;
				}
				$i++;
			}

			$scrollId = $data['_scroll_id'];
			$response = $this->client->request('_search/scroll', 'GET', $scrollId, array(
				'scroll' => $scroll,
			));

			$data = $response->getData();
		}
	}
}
