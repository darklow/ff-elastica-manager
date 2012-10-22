<?php
namespace FF\ElasticaManager;

use Elastica_Client;
use FF\ElasticaManager\Exception\ElasticaManagerProviderTransformException;
use FF\ElasticaManager\Exception\ElasticaManagerProviderIteratorException;
use FF\ElasticaManager\Exception\ElasticaManagerNoProviderDataException;
use Elastica_Document;
use FF\ElasticaManager\Exception\ElasticaManagerIndexExistsException;
use FF\ElasticaManager\Exception\ElasticaManagerIndexNotFoundException;
use Elastica_Response;
use Elastica_Index;
use Elastica_Status;
use Elastica_Type;
use Elastica_Type_Mapping;

class IndexManager
{
	/** @var Elastica_Client */
	protected $client;

	/** @var IndexConfiguration */
	protected $configuration;

	/** @var Elastica_Status */
	protected $status;

	/** @var Elastica_Type[] */
	protected $types;

	protected $indexName;

	/**
	 * @param Elastica_Client $client
	 * @param IndexConfiguration $configuration
	 * @param $indexName
	 */
	function __construct(Elastica_Client $client, IndexConfiguration $configuration, $indexName)
	{
		$this->client        = $client;
		$this->configuration = $configuration;
		$this->indexName     = $indexName;
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
		return $this->getConfiguration()->getProvider();
	}

	/**
	 * @return IndexConfiguration
	 */
	public function getConfiguration()
	{
		return $this->configuration;
	}

	/**
	 * @return string|null
	 */
	public function getIndexName()
	{
		return $this->indexName;
	}

	/**
	 * @param bool $dropIfExists
	 * @internal param $configurationName
	 * @throws ElasticaManagerIndexExistsException
	 * @return Elastica_Index
	 */
	public function create($dropIfExists = false)
	{
		if (!$dropIfExists && $this->indexExists()) {
			throw new ElasticaManagerIndexExistsException($this->indexName);
		}

		$elasticaIndex = $this->client->getIndex($this->indexName);
		$elasticaIndex->create($this->configuration->getConfig(), $dropIfExists);
		$this->setMapping($elasticaIndex);
		$this->refreshStatus();

		return $elasticaIndex;
	}

	/**
	 * @return Elastica_Response
	 */
	public function delete()
	{
		$deleteResponse = $this->getIndex(false)->delete();
		$this->refreshStatus();
		return $deleteResponse;
	}

	/**
	 * @param string|null $indexName
	 * @return bool
	 */
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

	/**
	 * Refresh elastica status
	 */
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
	public function getIndex($createIfMissing = false)
	{
		if (!$this->indexExists($this->indexName)) {
			if (!$createIfMissing) {
				throw new ElasticaManagerIndexNotFoundException($this->indexName);
			}
			$elasticaIndex = $this->create();
		} else {
			$elasticaIndex = $this->client->getIndex($this->indexName);
		}

		return $elasticaIndex;
	}

	/**
	 * @param null $typeName
	 * @param callable|null $closure
	 * @param bool $deleteIfExists
	 * @throws ElasticaManagerProviderIteratorException
	 * @throws ElasticaManagerNoProviderDataException
	 * @throws ElasticaManagerProviderTransformException
	 * @return Elastica_Index
	 */
	public function populate($typeName = null, \Closure $closure = null, $deleteIfExists = true)
	{
		if ($deleteIfExists && $this->indexExists()) {
			$this->delete();
		}

		$elasticaIndex = $this->getIndex(true);

		$provider        = $this->getProvider();
		$iterableResult  = $provider->getData($typeName);
		$total           = $provider->getTotal($typeName);
		$providerClosure = $provider->getIterationClosure();

		if (!$iterableResult) {
			throw new ElasticaManagerNoProviderDataException($this->getIndexName());
		}

		if (!is_array($iterableResult) && !$iterableResult instanceof \Traversable) {
			throw new ElasticaManagerProviderIteratorException($this->getIndexName());
		}

		$i = 0;
		foreach ($iterableResult as $data) {

			$closure and $closure($i, $total);
			$providerClosure and $providerClosure($i, $total);

			$providerDoc = $this->getProvider()->iterationRowTransform($data, $typeName);

			if (!$providerDoc instanceof DataProviderDocument) {
				throw new ElasticaManagerProviderTransformException($this->getIndexName());
			}

			$doc  = new Elastica_Document($providerDoc->getId(), $providerDoc->getData());
			$type = $this->getType($elasticaIndex, $providerDoc->getTypeName());
			$type->addDocument($doc);

			$i++;
		}

		// Refresh Index
		$elasticaIndex->refresh();

		return $elasticaIndex;
	}

	public function copy(\Closure $closure = null, $limit = null)
	{
	}

	public function addAlias($aliasName, $replace = false)
	{
		$this->getIndex()->addAlias($aliasName, $replace);
	}

	public function hasAlias($aliasName)
	{
		return $this->getStatus()->aliasExists($aliasName);
	}

	public function removeAlias($aliasName)
	{
		$status = static::getStatus();

		/** @var $indexesWithAlias Elastica_index[] */
		$indexesWithAlias = $status->getIndicesWithAlias($aliasName);
		foreach ($indexesWithAlias as $indexWithAlias) {
			$indexWithAlias->removeAlias($aliasName);
		}
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
