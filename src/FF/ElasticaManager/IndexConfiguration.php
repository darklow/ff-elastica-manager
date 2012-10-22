<?php
namespace FF\ElasticaManager;

abstract class IndexConfiguration implements IndexConfigurationInterface
{
	/**
	 * @var IndexDataProvider
	 */
	protected $provider;

	public function __construct(IndexDataProvider $provider)
	{
		$this->provider = $provider;
	}

	/**
	 * @return IndexDataProvider
	 */
	public function getProvider()
	{
		return $this->provider;
	}
}
