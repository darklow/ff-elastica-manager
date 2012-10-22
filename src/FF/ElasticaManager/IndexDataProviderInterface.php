<?php
namespace FF\ElasticaManager;

use Elastica_Type;

interface IndexDataProviderInterface
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
	 * @return \Closure|null
	 */
	public function getIterationClosure();

	/**
	 * Get data for one document
	 *
	 * @param $id
	 * @return array
	 */
	public function getDocumentData($id);

	/**
	 * Converts result iterator row to array containing three required keys: id, type, json
	 *
	 * @param $data
	 * @return array Array('id' => DocumentID, 'type' => TypeName, 'json' => JsonDataArray)
	 */
	public function iterationRowTransform($data);
}
