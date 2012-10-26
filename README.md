FF-Elastica-Manager
================

FF-Elastica-Manager is php library for creating, rotating, populating and managing ElasticSearch indexes using [Elastica client](https://github.com/ruflin/Elastica) library.

### Work in progress

This package is still under development, however these features are already implemented

## Features

* Index creation with user config and mapping
* Index data population using data provider
* Index one document update/insert
* Index utils: delete, indexExists, addAlias, removeAlias
* Batch Iterator. Iterates through index data (using ES scan/scroll functionality) and performs user specified closure
* Examples: ShopConfiguration.php, ShopDataProvider.php [Go to example directory](https://github.com/darklow/ff-elastica-manager/tree/master/example)

**Todo**: Index copy/clone, index rotate (copy and change alias), Symfony2 Console component commands

## Installation
The recommended way to install package is [through composer](http://getcomposer.org). Create a `composer.json` in your project root-directory:

    {
        "require": {
            "darklow/ff-silex-less-provider": "*"
        }
    }

and run ```curl -s http://getcomposer.org/installer | php``` to get composer or run ```php composer.phar install``` to install package


## Overview

ElasticaManager package contains of following classes:

1. **ElasticaManager** - working with indexes and elasticsearch server
2. **IndexManager** - create, delete, manage specific index
3. **Batch Iterator** - iterates through index data (using ES scan/scroll functionality) and performs user specified closure

For every index you want to manage, you have to create two classes:

1. **Configuration** - Configuration class which provides necessary info for ElasticSearch index:
    * Index default name - default name of the index (can be overridden on IndexManager initiation)
    * Index default alias name
    * Index types - ElasticSearch index type name(s)
    * Index configuration - number of shards and replicas, analysis analyzers and filters
    * Mapping properties - fields and its types for each/all ElasticSearch type
    * Mapping params - params like ```_all => [ enabled => false]``` and so on


2. **DataProvider** - Data provider class which provides all the data needed to populate whole index or just one document.
Following methods must be implemented:
    * getData($typeName = null) - Method must return iterable result/array for all the data or one type only if specified
    * iterationRowTransform($data, $typeName = null) - Method must convert iteration row data to DataProviderDocument object which contains three variables
        * id - DocumentID
        * typeName - ElasticSearch index type name
        * data - Array for document source data
    * getTotal($typeName = null) - Optional method. Must return count for all the data or one type only if specified used. Used for iteration closures.
    * getIterationClosure() - Optional method. Must return callback for iteration: function ($i, $total)
    * getDocumentData() - Method must return same type of data as one iteration row in getData()

Example of both classes can be found in [example directory](https://github.com/darklow/ff-elastica-manager/tree/master/example)

When you have setup up everything, working with indexes is really easy:

### IndexManager Example

```php
<?php
// Get IndexManager
$shopIndexManager = $elasticaManager->getIndexManager('shop');

// Use IndexManager
$shopIndexManager->create();
$shopIndexManager->populate();
$shopIndexManager->updateDocument(1);
$shopIndexManager->delete();
```

Every time you create index, your configuration and mappings are used and once populated your data is in the index.

### Batch iterator Example

```php
<?php
// Get IndexManager
$shopIndexManager = $elasticaManager->getIndexManager('shop');

// Get iterator instance
$iterator = $shopIndexManager->getIterator();

// Specify query
$query = new Elastica_Query(new Elastica_Query_MatchAll());

// Define closure
$closure = function (DataProviderDocument $doc, $i, $total) {
	// Do whatever you like with $doc->getData()
};

// Start iterating
$iterator->iterate($query, $closure);
```

Read more on how to setup initial classes in documentation.

## Documentation

Read full documentation on how to initiate and use ElasticaManager and IndexManager here:

[Documentation wiki](https://github.com/darklow/ff-elastica-manager/wiki)


## License

'FF-Elastica-Manager' is licensed under the MIT license.

