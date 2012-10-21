<?php
namespace FF\ElasticaManager;

use Elastica_Type;

interface IndexDataProviderInterface
{
	/**
	 * Get iteratable result/array
	 *
	 * @param $typeName
	 * @param null|int $total
	 * @param null $providerClosure
	 * @return array
	 */
	public function getData($typeName = null, &$total = null, &$providerClosure = null);

	/**
	 * Get data for one document
	 *
	 * @param $id
	 * @param null $typeName
	 * @return array
	 */
	public function getDocumentData($id, &$typeName = null);

	/**
	 * Converts result iterator row to document json array
	 * @param $data
	 * @param string|null $typeName
	 * @param $id
	 * @return array
	 */
	public function dataToArray($data, &$typeName = null, &$id = null);
}
