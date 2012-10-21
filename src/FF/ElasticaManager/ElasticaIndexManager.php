<?php
namespace FF\ElasticaManager;

use Elastica_Client;
use Elastica_Document;
use FF\ElasticaManager\Exception\ElasticaManagerIndexExistsException;
use FF\ElasticaManager\Exception\ElasticaManagerIndexNotFoundException;
use Elastica_Response;
use Elastica_Index;
use Elastica_Status;
use Elastica_Type;
use Elastica_Type_Mapping;

class ElasticaIndexManager
{
	/** @var Elastica_Client */
	protected $client;

	/** @var IndexConfiguration */
	protected $configuration;

	/** @var IndexDataProvider */
	protected $provider;

	/** @var Elastica_Status */
	protected $status;

	/** @var Elastica_Type[] */
	protected $types;

	/** @var Elastica_Index */
	protected $index;

	protected $indexName;

	function __construct(Elastica_Client $client, IndexConfiguration $configuration, IndexDataProvider $provider)
	{
		$this->client        = $client;
		$this->configuration = $configuration;
		$this->provider      = $provider;
		$this->indexName     = $this->configuration->getName();
	}

	/**
	 * @return Elastica_Client
	 */
	public function getClient()
	{
		return $this->client;
	}

	/**
	 * @return IndexDataProvider
	 */
	public function getProvider()
	{
		return $this->provider;
	}

	/**
	 * @return IndexConfiguration
	 */
	public function getConfiguration()
	{
		return $this->configuration;
	}

	public function getIndexName()
	{
		return $this->indexName;
	}

	public function setIndexName($indexName)
	{
		$this->indexName = $indexName;
	}

	/**
	 * @param bool $recreteIfExists
	 * @internal param $configurationName
	 * @throws ElasticaManagerIndexExistsException
	 * @return Elastica_Index
	 */
	public function createIndex($recreteIfExists = false)
	{
		if (!$recreteIfExists && $this->indexExists()) {
			throw new ElasticaManagerIndexExistsException($this->indexName);
		}

		$elasticaIndex = $this->client->getIndex($this->indexName);
		$elasticaIndex->create($this->configuration->getConfig(), $recreteIfExists);
		$this->setMapping($elasticaIndex);
		$this->refreshStatus();

		return $elasticaIndex;
	}

	/**
	 * @return Elastica_Response
	 */
	public function deleteIndex()
	{
		$deleteResponse = $this->getIndex(false)->delete();
		$this->refreshStatus();
		return $deleteResponse;
	}

	public function indexExists($indexName = null)
	{
		return $this->getStatus()->indexExists($indexName ? : $this->indexName);
	}

	/**
	 * @return Elastica_Status
	 */
	protected function getStatus()
	{
		return $this->status ? : $this->status = new Elastica_Status($this->client);
	}

	protected function refreshStatus()
	{
		if ($this->status) {
			$this->status->refresh();
		}
	}

	/**
	 * @param Elastica_Index $elasticaIndex
	 */
	protected function setMapping(Elastica_Index $elasticaIndex)
	{
		foreach ($this->configuration->getTypes() as $typeName) {
			$mapping = new Elastica_Type_Mapping();
			$type    = $elasticaIndex->getType($typeName);
			$mapping->setType($type);

			// Set properties
			$mapping->setProperties($this->configuration->getMappingProperties($type));

			// Set params if any
			if ($mappingParams = $this->configuration->getMappingParams($type)) {
				foreach ($mappingParams as $mappingParam => $mappingParamValue) {
					$mapping->setParam($mappingParam, $mappingParamValue);
				}
			}

			$mapping->send();
		}
	}

	/**
	 * @param bool $createIfMissing
	 * @throws ElasticaManagerIndexNotFoundException
	 * @return Elastica_Index
	 */
	protected function getIndex($createIfMissing = false)
	{
		if (!$this->indexExists($this->indexName)) {
			if (!$createIfMissing) {
				throw new ElasticaManagerIndexNotFoundException($this->indexName);
			}
			$elasticaIndex = $this->createIndex();
		} else {
			$elasticaIndex = $this->client->getIndex($this->indexName);
		}

		return $elasticaIndex;
	}

	/**
	 * @param null $typeName
	 * @param callable|null $closure
	 * @param bool $deleteIfExists
	 * @return Elastica_Index
	 */
	public function populate($typeName = null, \Closure $closure = null, $deleteIfExists = true)
	{
		if ($deleteIfExists) {
			$this->deleteIndex();
		}

		$elasticaIndex = $this->getIndex(true);

		$iterableResult = $this->provider->getData($typeName, $total, $providerClosure);
		if (!$iterableResult) {
			return;
		}

		$i = 0;
		foreach ($iterableResult as $dataKey => $data) {

			$closure and $closure($i, $total);
			$providerClosure and $providerClosure($i, $total);

			$data = $this->provider->dataToArray($data, $typeName, $id);
			$doc  = new Elastica_Document($id ? : $dataKey, $data);
			$type = $this->getType($elasticaIndex, $typeName);
			$type->addDocument($doc);

			$i++;
		}

		// Refresh Index
		$elasticaIndex->refresh();

		return $elasticaIndex;
	}

	/**
	 * @param Elastica_Index $index
	 * @param null $typeName
	 * @return Elastica_Type
	 */
	protected function getType(Elastica_Index $index, $typeName)
	{
		if (isset($this->types[$typeName])) {
			return $this->types[$typeName];
		}
		return $this->types[$typeName] = $index->getType($typeName);
	}
}
