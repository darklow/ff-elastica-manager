<?php
namespace FF\ElasticaManager;

abstract class Configuration implements ConfigurationInterface
{
	/**
	 * @var DataProvider
	 */
	protected $provider;

	/**
	 * @param DataProvider $provider
	 */
	public function __construct(DataProvider $provider)
	{
		$this->provider = $provider;
	}

	/**
	 * @return DataProvider
	 */
	public function getProvider()
	{
		return $this->provider;
	}

	/**
	 * Class to string conversion
	 * @return string
	 */
	public function __toString()
	{
		return (string)$this->getName();
	}

	/**
	 * Get default alias name
	 * @return string|null
	 */
	public function getAlias()
	{
	}
}
