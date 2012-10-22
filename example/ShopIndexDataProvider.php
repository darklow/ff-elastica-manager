<?php
namespace Example;

use FF\ElasticaManager\IndexDataProvider;
use FF\ElasticaManager\DataProviderDocument;

class ShopIndexDataProvider extends IndexDataProvider
{
	protected $total;

	/**
	 * {@inheritDoc}
	 */
	public function getData($typeName = null)
	{
		$data = array(
			array(
				'type'   => 'book',
				'id'     => 1,
				'name'   => 'Fight Club',
				'author' => 'Chuck Palahniuk'
			),
			array(
				'type'   => 'book',
				'id'     => 2,
				'name'   => 'Jonathan Livingston Seagull',
				'author' => 'Richard Bach'
			),
			array(
				'type'     => 'dvd',
				'id'       => 3,
				'name'     => 'The Beach',
				'released' => '2000-02-11'
			),
			array(
				'type'     => 'dvd',
				'id'       => 4,
				'name'     => 'TRON: Legacy',
				'released' => '2010-12-07'
			)
		);

		if ($typeName) {
			$data = array_filter($data, function ($value) use ($typeName) {
				return $value['type'] == $typeName;
			});
		}

		// Set total
		$this->total = count($data);

		return $data;
	}

	public function getTotal($typeName = null)
	{
		return $this->total;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDocumentData($id, &$typeName = null)
	{
	}

	/**
	 * {@inheritDoc}
	 */
	public function iterationRowTransform($data, $typeName = null)
	{
		$finalData = $data;
		unset($finalData['id'], $finalData['type']);
		return new DataProviderDocument($data['id'], $data['type'], $finalData);
	}
}
