<?php
namespace FF\ElasticaManager;

use FF\ElasticaManager\Exception\ElasticaManagerNoAliasException;
use FF\ElasticaManager\Exception\ElasticaManagerProviderTransformException;
use FF\ElasticaManager\Exception\ElasticaManagerProviderIteratorException;
use FF\ElasticaManager\Exception\ElasticaManagerNoProviderDataException;
use FF\ElasticaManager\Exception\ElasticaManagerIndexExistsException;
use FF\ElasticaManager\Exception\ElasticaManagerIndexNotFoundException;
use Elastica_Client;
use Elastica_Document;
use Elastica_Response;
use Elastica_Index;
use Elastica_Status;
use Elastica_Type;
use Elastica_Type_Mapping;

class IndexManager
{
	/** @var Elastica_Client */
	protected $client;

	/** @var Configuration */
	protected $configuration;

	/** @var Iterator */
	protected $iterator;

	/** @var Elastica_Status */
	protected $status;

	/** @var Elastica_Type[] */
	protected $types;

	protected $indexName;

	/**
	 * @param Elastica_Client $client
	 * @param Configuration $configuration
	 * @param $indexName
	 */
	function __construct(Elastica_Client $client, Configuration $configuration, $indexName)
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
	 * @return DataProvider
	 */
	public function getProvider()
	{
		return $this->getConfiguration()->getProvider();
	}

	/**
	 * @return Configuration
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
		$elasticaIndex->create($this->configuration->getConfig() ? : array(), $dropIfExists);
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
			if ($properties = $this->configuration->getMappingProperties($type)) {
				$mapping->setProperties($properties);
			}

			// Set params if any
			if ($mappingParams = $this->configuration->getMappingParams($type)) {
				foreach ($mappingParams as $mappingParam => $mappingParamValue) {
					$mapping->setParam($mappingParam, $mappingParamValue);
				}
			}

			if ($mappingParams || $properties) {
				$mapping->send();
			}
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
	 * @return Elastica_Index
	 * @throws ElasticaManagerIndexNotFoundException
	 */
	public function getIndexByAlias()
	{
		$defaultAlias = $this->getDefaultAlias();
		if (!$this->hasAlias($defaultAlias)) {
			throw new ElasticaManagerIndexNotFoundException($defaultAlias, true);
		}
		$elasticaIndex = $this->client->getIndex($defaultAlias);
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

			$this->transformAndAddDocument($elasticaIndex, $data, $typeName);

			$i++;
		}

		// Refresh Index
		$elasticaIndex->refresh();

		return $elasticaIndex;
	}

	/**
	 * Update document or insert if does not exist
	 *
	 * @param $id
	 * @param null $typeName
	 * @return Elastica_Response
	 */
	public function updateDocument($id, $typeName = null)
	{
		$elasticaIndex = $this->getIndex(true);
		$data          = $this->getProvider()->getDocumentData($id, $typeName);
		return $this->transformAndAddDocument($elasticaIndex, $data, $typeName);
	}

	protected function transformAndAddDocument($elasticaIndex, $data, $typeName)
	{
		$providerDoc = $this->getProvider()->iterationRowTransform($data, $typeName);

		if (!$providerDoc instanceof DataProviderDocument) {
			throw new ElasticaManagerProviderTransformException($this->getIndexName());
		}

		$doc  = new Elastica_Document($providerDoc->getId(), $providerDoc->getData());
		$type = $this->getType($elasticaIndex, $providerDoc->getTypeName());

		return $type->addDocument($doc);
	}

	public function copy(\Closure $closure = null, $limit = null)
	{
	}

	/**
	 * @param $aliasName
	 * @param bool $replace OPTIONAL If set, an existing alias will be replaced
	 */
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
	 * @param bool $replace OPTIONAL If set, an existing alias will be replaced
	 */
	public function addDefaultAlias($replace = false)
	{
		$defaultAlias = $this->getDefaultAlias();
		$this->addAlias($defaultAlias, $replace);
	}

	public function removeDefaultAlias()
	{
		$defaultAlias = $this->getDefaultAlias();
		$this->removeAlias($defaultAlias);
	}

	protected function getDefaultAlias()
	{
		$defaultAlias = $this->configuration->getAlias();
		if (!$defaultAlias) {
			throw new ElasticaManagerNoAliasException($this->configuration->getName());
		}
		return $defaultAlias;
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

	public function getIterator()
	{
		if ($this->iterator) {
			return $this->iterator;
		}

		return $this->iterator = new Iterator($this->client, $this->getIndex());
	}
}
