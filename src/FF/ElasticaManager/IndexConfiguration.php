<?php
namespace FF\ElasticaManager;

abstract class IndexConfiguration implements IndexConfigurationInterface
{
	protected $name;

	public function __construct($name = null)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name ? : $this->getDefaultName();
	}
}
