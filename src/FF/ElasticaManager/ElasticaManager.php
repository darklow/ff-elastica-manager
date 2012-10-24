<?php
namespace FF\ElasticaManager;

use Elastica_Client;

class ElasticaManager
{
	/** @var Elastica_Client */
	protected $client;

	/** @var Configuration[] */
	protected $configurations;

	/** @var DataProvider[] */
	protected $providers;

	/** @var IndexManager[] */
	protected $indexManagers;

	public function __construct(Elastica_Client $client)
	{
		$this->client = $client;
	}

	/**
	 * @return Elastica_Client
	 */
	public function getClient()
	{
		return $this->client;
	}

	public function addConfiguration(Configuration $configuration)
	{
		$this->configurations[$configuration->getName()] = $configuration;
	}

	/**
	 * @param $configurationName
	 * @throws \InvalidArgumentException
	 * @return Configuration
	 */
	public function getConfiguration($configurationName)
	{
		if (!isset($this->configurations[$configurationName])) {
			throw new \InvalidArgumentException("Configuration by name \"$configurationName\" doesn't exist");
		}
		return $this->configurations[$configurationName];
	}

	/**
	 * @param $configurationName
	 * @param $indexName
	 * @return IndexManager
	 */
	public function getIndexManager($configurationName, $indexName = null)
	{
		$configuration = $this->getConfiguration($configurationName);
		$indexName     = $indexName ? : $configuration->getName();
		if (isset($this->indexManagers[$indexName])) {
			return $this->indexManagers[$indexName];
		}

		return $this->indexManagers[$indexName] = new IndexManager($this->client, $configuration, $indexName);
	}

	/**
	 * Returns all registered configurations
	 * Useful note: Configuration class has __toString method returning default index name
	 * @return Configuration[]
	 */
	public function getConfigurations()
	{
		return $this->configurations;
	}
}
