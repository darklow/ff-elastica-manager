FF-Elastica-Manager
================

Elastica manager for creating, rotating, populating and managing ElasticSearch indexes using Elastica client library


Complete
-------
Methods: create, delete, populate

Examples: ShopConfiguration.php, ShopDataProvider.php


Todo
-------

Methods: rotate, copy

Examples: ElasticaSymfonyCommand.php


## Installation
The recommended way to install package is [through composer](http://getcomposer.org). Create a `composer.json` in your project root-directory:

    {
        "require": {
            "darklow/ff-silex-less-provider": "*"
        }
    }

and run:

    curl -s http://getcomposer.org/installer | php
    php composer.phar install


## Getting started

Use following steps to start using elastica manager

    // Create client
    $client  = new Elastica_Client(array(
        'servers' => array(
            array(
                'host' => '192.168.0.223',
                'port' => 9200
            )
        )
    ));
    
    // Create Elastica manager
    $elasticaManager = new ElasticaManager($this->client);

Now you have to create Configuration and DataProvider classes for you index.

You will find ShopConfiguration and ShopDataProvider example classes in [example directory](https://github.com/darklow/ff-elastica-manager/tree/master/example).

Once you create these classes you can add them to elastica manager

    // Create your index configuration with data provider
    $provider = new ShopDataProvider();
    $configuration = new ShopConfiguration($provider);
    
    // Add configuration(s) to the manager
    $elasticaManager->addConfiguration($configuration);

Now you have successfully setup ElasticaManager you can get IndexManager using following code:

    $indexManager = $elasticaManager->getIndexManager('shop');
    
    // or you could use configuration constant
    $indexManager = $elasticaManager->getIndexManager(ShopConfiguration::NAME);


Now you have $indexManager and you can start using it's methods

## Methods

#### Create index

Following line will create new index or throw an exception if index will already exist

    $index = $indexManager->create();
    
You can set argument create() $dropIfExists to TRUE if you wish to avoid exception and drop existing index

    $index = $indexManager->create(true);

License
-------

'FF-Elastica-Manager' is licensed under the MIT license.
