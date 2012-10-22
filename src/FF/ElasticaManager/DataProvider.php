<?php
namespace FF\ElasticaManager;

abstract class DataProvider implements DataProviderInterface
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
