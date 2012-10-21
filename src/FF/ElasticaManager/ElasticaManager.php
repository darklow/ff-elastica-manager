<?php
namespace FF\ElasticaManager;

use Elastica_Client;
use Elastica_Index;
use Elastica_Status;
use Elastica_Type;
use Elastica_Type_Mapping;

class ElasticaManager
{
	/** @var Elastica_Client */
	protected $client;

	/** @var Elastica_Status */
	protected $status;

	/** @var Elastica_Type[] */
	protected $types;

	/** @var IndexConfiguration[] */
	protected $configurations = array();

	function __construct(Elastica_Client $client)
	{
		$this->client = $client;
	}

	/**
	 * @param $configurationName
	 * @param null $indexName
	 * @param bool $recreteIfExists
	 * @return Elastica_Index
	 * @throws \InvalidArgumentException
	 */
	public function createIndex($configurationName, $indexName = null, $recreteIfExists = false)
	{
		$configuration = $this->getConfiguration($configurationName, $indexName);
		$indexName     = $configuration->getName();

		if (!$recreteIfExists && $this->indexExists($indexName)) {
			throw new \InvalidArgumentException("Index with name \"$indexName\" already exists");
		}

		$elasticaIndex = $this->client->getIndex($indexName);
		$elasticaIndex->create($configuration->getConfig(), $recreteIfExists);
		$this->setMapping($configuration, $elasticaIndex);
		$this->refreshStatus();

		return $elasticaIndex;
	}

	/**
	 * @param IndexConfiguration $configuration
	 * @param Elastica_Index $elasticaIndex
	 */
	protected function setMapping(IndexConfiguration $configuration, Elastica_Index $elasticaIndex)
	{
		foreach ($configuration->getTypes() as $typeName) {
			$mapping = new Elastica_Type_Mapping();
			$type    = $elasticaIndex->getType($typeName);
			$mapping->setType($type);

			// Set properties
			$mapping->setProperties($configuration->getMappingProperties($type));

			// Set params if any
			if ($mappingParams = $configuration->getMappingParams($type)) {
				foreach ($mappingParams as $mappingParam => $mappingParamValue) {
					$mapping->setParam($mappingParam, $mappingParamValue);
				}
			}

			$mapping->send();
		}
	}

	/**
	 * @param $configurationName
	 * @param null $indexName
	 * @param callable $closure
	 */
	public function populate($configurationName, $indexName = null, \Closure $closure = null)
	{
		$elasticaIndex = $this->getIndex($configurationName, $indexName, true);

	}

	/**
	 * @param $configurationName
	 * @param null $indexName
	 * @param bool $createIfMissing
	 * @return Elastica_Index
	 * @throws \InvalidArgumentException
	 */
	protected function getIndex($configurationName, $indexName = null, $createIfMissing = false)
	{
		$configuration = $this->getConfiguration($configurationName, $indexName);
		$indexName     = $configuration->getName();

		if (!$this->indexExists($indexName)) {
			if (!$createIfMissing) {
				throw new \InvalidArgumentException("Index with name \"$indexName\" doesn't exist");
			}
			$elasticaIndex = $this->createIndex($configurationName, $indexName);
		} else {
			$elasticaIndex = $this->client->getIndex($indexName);
		}

		return $elasticaIndex;
	}

	protected function indexExists($indexName)
	{
		return $this->getStatus()->indexExists($indexName);
	}

	/**
	 * @param $configurationName
	 * @param null $indexName
	 * @return IndexConfiguration
	 */
	protected function getConfiguration($configurationName, $indexName = null)
	{
		if (isset($this->configurations[$configurationName])) {
			return $this->configurations[$configurationName];
		}

		$className = $this->getNamespace().ucfirst($configurationName).'IndexConfiguration';
		return $this->configurations[$configurationName] = new $className($indexName);
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
	 * @param $indexName
	 * @return IndexDataProviderInterface
	 */
	public function getIndexDataProvider($indexName)
	{
		$className = $this->getNamespace($indexName).ucfirst($indexName).'IndexDataProvider';
		return new $className($this->em);
	}

	public function getNamespace()
	{
		return str_replace('ElasticaManager', '', __CLASS__);
	}
}
