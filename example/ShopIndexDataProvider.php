<?php
namespace Example;

use FF\ElasticaManager\IndexDataProvider;

class ShopIndexDataProvider extends IndexDataProvider
{
	/**
	 * {@inheritDoc}
	 */
	public function getData($typeName = null, &$total = null, &$providerClosure = null)
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
		$total = count($data);

		// Do some memory clear in provider closure if needed
		$providerClosure = function ($i, $total) {
		};

		return $data;
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
	public function dataToArray($data, &$typeName = null, &$id = null)
	{
		$id       = $data['id'];
		$typeName = $data['type'];

		unset($data['id'], $data['type']);

		return $data;
	}
}
