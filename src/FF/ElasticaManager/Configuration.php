<?php
namespace FF\ElasticaManager;

abstract class Configuration implements ConfigurationInterface
{
	/**
	 * @var DataProvider
	 */
	protected $provider;

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
}
