<?php
namespace FF\ElasticaManager;

use Elastica\Type;

interface ConfigurationInterface
{
	public function __construct(DataProvider $provider);

	public function getName();

	public function getTypes();

	public function getAlias();

	public function getConfig();

	public function getMappingParams(Type $type);

	public function getMappingProperties(Type $type);
}
