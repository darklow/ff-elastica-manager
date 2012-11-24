<?php
namespace FF\ElasticaManager;

use FF\ElasticaManager\DataProviderDocument;

interface DataProviderInterface
{
	/**
	 * Get iterable result/array
	 *
	 * @param $typeName
	 * @return array
	 */
	public function getData($typeName = null);

	/**
	 * Get count of documents. Optional method. Used for closures.
	 * This method is always called after getData() method
	 * Therefore you can add some class level variable for storing $total
	 *
	 * @param null $typeName
	 * @return int
	 */
	public function getTotal($typeName = null);

	/**
	 * Define closure for data provider if needed. Useful for some memory clear for entity managers etc.
	 * Closure receives two arguments iterator index and total count: function ($i, $total)
	 *
	 * @return \Closure
	 */
	public function getIterationClosure();

	/**
	 * Get data for one document
	 * Must return same type of data as one iteration row in getData()
	 *
	 * @param $id
	 * @param string|null $typeName
	 * @return DataProviderDocument
	 */
	public function getDocumentData($id, $typeName = null);

	/**
	 * Converts result iterator row to DataProviderDocument
	 * If indexManager->populate($typeName = null) is called with $typeName argument, then typeName is forwarded to this method too
	 *
	 * @param $data
	 * @param null $typeName
	 * @return DataProviderDocument
	 */
	public function iterationRowTransform($data, $typeName = null);
}
