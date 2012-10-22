<?php
namespace FF\ElasticaManager;

use Elastica_Type;

interface IndexConfigurationInterface
{
	public function __construct(IndexDataProvider $provider);

	public function getName();

	public function getTypes();

	public function getConfig();

	public function getMappingParams(Elastica_Type $type);

	public function getMappingProperties(Elastica_Type $type);
}
