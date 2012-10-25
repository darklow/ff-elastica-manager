<?php
namespace ElasticaManager\Tests;

use ElasticaManager\Tests\Configuration\TestDataProvider;
use ElasticaManager\Tests\Configuration\TestConfiguration;

class ConfigurationTest extends ElasticaManagerTestBase
{
	public function testConstruct()
	{
		$provider      = new TestDataProvider();
		$configuration = new TestConfiguration($provider);
		$this->assertEquals($provider, $configuration->getProvider());
	}

	public function testToString()
	{
		$configuration = new TestConfiguration(new TestDataProvider());
		$this->assertEquals($configuration::NAME, (string)$configuration);
	}

	public function testAlias()
	{
		$configuration = new TestConfiguration(new TestDataProvider());
		$this->assertEquals($configuration::ALIAS, $configuration->getAlias());
	}
}
