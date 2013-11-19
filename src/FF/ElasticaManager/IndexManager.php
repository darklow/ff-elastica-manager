<?php
namespace FF\ElasticaManager;

use FF\ElasticaManager\Exception\ElasticaManagerNoAliasException;
use FF\ElasticaManager\Exception\ElasticaManagerProviderTransformException;
use FF\ElasticaManager\Exception\ElasticaManagerProviderIteratorException;
use FF\ElasticaManager\Exception\ElasticaManagerNoProviderDataException;
use FF\ElasticaManager\Exception\ElasticaManagerIndexExistsException;
use FF\ElasticaManager\Exception\ElasticaManagerIndexNotFoundException;
use Elastica\Client;
use Elastica\Document;
use Elastica\Response;
use Elastica\Index;
use Elastica\Status;
use Elastica\Type;
use Elastica\Type\Mapping;

class IndexManager
{
	const EXISTS_BY_NAME  = 1;
	const EXISTS_BY_ALIAS = 2;

	/** @var Client */
	protected $client;

	/** @var Configuration */
	protected $configuration;

	/** @var Iterator */
	protected $iterator;

	/** @var Status */
	protected $status;

	/** @var Type[] */
	protected $types;

	protected $indexName;

	/**
	 * @param Client $client
	 * @param Configuration $configuration
	 * @param $indexName
	 */
	function __construct(Client $client, Configuration $configuration, $indexName)
	{
		$this->client        = $client;
		$this->configuration = $configuration;
		$this->indexName     = $indexName;
	}

	/**
	 * @return Client
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
	 * @return Index
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
	 * @param bool $findIndexByAlias
	 * @return Response
	 */
	public function delete($findIndexByAlias = false)
	{
		$elasticaIndex  = $findIndexByAlias ? $this->getIndexByAlias() : $this->getIndex();
		$deleteResponse = $elasticaIndex->delete();
		$this->refreshStatus();
		return $deleteResponse;
	}

	/**
	 * Verify if index exists
	 *
	 * @return bool
	 */
	public function indexExists()
	{
		return $this->getStatus()->indexExists($this->indexName);
	}

	/**
	 * Verify if index exists by default alias name
	 *
	 * @return bool
	 */
	public function indexExistsByAlias()
	{
		return $this->hasAlias($this->getDefaultAlias());
	}

	/**
	 * @return Status
	 */
	protected function getStatus()
	{
		return $this->status ? : $this->status = new Status($this->client);
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
	 * @param Index $elasticaIndex
	 */
	protected function setMapping(Index $elasticaIndex)
	{
		foreach ($this->configuration->getTypes() as $typeName) {
			$mapping = new Mapping();
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
	 * Get index
	 * By default index is selected by indexName or if not found then by aliasName if such specified in configuration
	 *
	 * @param bool $createIfMissing
	 * @throws ElasticaManagerIndexNotFoundException
	 * @return Index
	 */
	public function getIndex($createIfMissing = false)
	{
		if (!$indexExists = $this->indexExists()) {
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
	 * @return Index
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
	 * @param bool $findIndexByAlias
	 * @throws ElasticaManagerProviderIteratorException
	 * @throws ElasticaManagerNoProviderDataException
	 * @return Index
	 */
	public function populate($typeName = null, \Closure $closure = null, $deleteIfExists = true, $findIndexByAlias = false)
	{
		if ($deleteIfExists && $this->indexExists()) {
			$this->delete();
		}

		$elasticaIndex = $findIndexByAlias ? $this->getIndexByAlias() : $this->getIndex(true);

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
	 * Because it calls index and status refresh - use this method to update few documents only.
	 * Use populate() method to update larger amounts of documents
	 *
	 * @param $id
	 * @param null $typeName
	 * @param bool $findIndexByAlias
	 * @return Response
	 */
	public function updateDocument($id, $typeName = null, $findIndexByAlias = false)
	{
		$elasticaIndex = $findIndexByAlias ? $this->getIndexByAlias() : $this->getIndex();
		$data          = $this->getProvider()->getDocumentData($id, $typeName);
		$result        = $this->transformAndAddDocument($elasticaIndex, $data, $typeName);

		$this->refreshStatus();

		// Refresh Index
		$elasticaIndex->refresh();

		return $result;
	}

	/**
	 * Delete document by id only or by id and type
	 * Note: If your index does not have unique ID for all types do not forget to specify typeName!
	 *
	 * @param $id
	 * @param null $typeName
	 * @param bool $findIndexByAlias
	 * @return Response
	 */
	public function deleteDocument($id, $typeName = null, $findIndexByAlias = false)
	{
		$elasticaIndex = $findIndexByAlias ? $this->getIndexByAlias() : $this->getIndex();
		if ($typeName) {
			$response = $elasticaIndex->getType($typeName)->deleteById($id);
		} else {
			$response = $elasticaIndex->request('_query', 'DELETE', array('ids' => array('values' => array($id))));
		}

		$this->refreshStatus();

		// Refresh Index
		$elasticaIndex->refresh();

		return $response;
	}

	/**
	 * @param $elasticaIndex
	 * @param $data
	 * @param $typeName
	 * @return Response
	 * @throws ElasticaManagerProviderTransformException
	 */
	protected function transformAndAddDocument($elasticaIndex, $data, $typeName)
	{
		$providerDoc = $this->getProvider()->iterationRowTransform($data, $typeName);

		if (!$providerDoc instanceof DataProviderDocument) {
			throw new ElasticaManagerProviderTransformException($this->getIndexName());
		}

		$doc  = new Document($providerDoc->getId(), $providerDoc->getData());
		$type = $this->getType($elasticaIndex, $providerDoc->getTypeName());

		return $type->addDocument($doc);
	}

	/**
	 * Todo Not implemented yet
	 */
	public function copy(\Closure $closure = null, $limit = null)
	{
	}

	/**
	 * @param $aliasName
	 * @param bool $replace OPTIONAL If set, an existing alias will be replaced
	 * @return Response
	 */
	public function addAlias($aliasName, $replace = false)
	{
		return $this->getIndex()->addAlias($aliasName, $replace);
	}

	/**
	 * Verifies if current index has alias name
	 *
	 * @param $aliasName
	 * @return bool
	 */
	public function hasAlias($aliasName)
	{
		return $this->getStatus()->aliasExists($aliasName);
	}

	/**
	 * Removes alias from current index
	 * @param $aliasName
	 */
	public function removeAlias($aliasName)
	{
		$status = static::getStatus();

		/** @var $indexesWithAlias Index[] */
		$indexesWithAlias = $status->getIndicesWithAlias($aliasName);
		foreach ($indexesWithAlias as $indexWithAlias) {
			$indexWithAlias->removeAlias($aliasName);
		}
	}

	/**
	 * @param bool $replace OPTIONAL If set, an existing alias will be replaced
	 * @return Response
	 */
	public function addDefaultAlias($replace = false)
	{
		$defaultAlias = $this->getDefaultAlias();
		return $this->addAlias($defaultAlias, $replace);
	}

	/**
	 * Removes default alias
	 */
	public function removeDefaultAlias()
	{
		$defaultAlias = $this->getDefaultAlias();
		$this->removeAlias($defaultAlias);
	}

	/**
	 * Returns default alias name
	 *
	 * @return null|string
	 * @throws ElasticaManagerNoAliasException
	 */
	protected function getDefaultAlias()
	{
		$defaultAlias = $this->configuration->getAlias();
		if (!$defaultAlias) {
			throw new ElasticaManagerNoAliasException($this->configuration->getName());
		}
		return $defaultAlias;
	}

	/**
	 * @param Index $index
	 * @param null $typeName
	 * @return Type
	 */
	protected function getType(Index $index, $typeName)
	{
		if (isset($this->types[$typeName])) {
			return $this->types[$typeName];
		}
		return $this->types[$typeName] = $index->getType($typeName);
	}

	/**
	 * Get iterator for performing batch tasks
	 * Iterator can iterate through index data (using ES scan/scroll functionality) and perform user specified closure
	 *
	 * @param bool $findIndexByAlias
	 * @return Iterator
	 */
	public function getIterator($findIndexByAlias = false)
	{
		if ($this->iterator) {
			return $this->iterator;
		}

		return $this->iterator = new Iterator($this->client, $findIndexByAlias ? $this->getIndexByAlias() : $this->getIndex());
	}
}
