<?php
namespace FF\ElasticaManager;

abstract class IndexDataProvider implements IndexDataProviderInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function getTotal($typeName = null)
	{
		// Override this method if needed
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIterationClosure()
	{
		// Override this method if needed
	}
}
