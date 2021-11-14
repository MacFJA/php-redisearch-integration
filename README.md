# PHP RediSearch Integration

[MacFJA/redisearch-integration](https://packagist.org/packages/macfja/redisearch-integration) is a small library to ease usage of [MacFJA/redisearch](https://packagist.org/packages/macfja/redisearch) which is a RediSearch client.

## Installation

```
composer require macfja/redisearch-integration
```

## Usage

You will mainly use `ObjectManager` (`\MacFJA\RediSearch\Integration\ObjectManager`) and `ObjectRepository` (`\MacFJA\RediSearch\Integration\ObjectRepository`).  

This 2 interfaces are bundle into the `ObjectWorker` (`\MacFJA\RediSearch\Integration\ObkectWorker`) class, to ease usage.
_(If you are using a good Injection dependency, you will only need the interface)_

```php
use MacFJA\RediSearch\Integration\CompositeProvider;
use MacFJA\RediSearch\Integration\ObjectWorker;
use MacFJA\RediSearch\Redis\Client\ClientFacade;

$jsonProvider = new JsonProvider();
$jsonProvider->addJson(__DIR__.'/mappings.json');
$provider = new CompositeProvider(); // Annotation, Attribute, class implementation
$provider->addProvider($jsonProvider); // Add JSON as provider source

$client = (new ClientFacade())->getClient(/* .. */);

$manager = new ObjectWorker($client, $provider);
// ...
$manager->createIndex(\MyApp\Model\Product::class);
// $manager->flush(); If you need the index to create directly 
// ...
$entity = \MyApp\Model\Product();
$manager->persist($entity); // Add object in search and build suggestions
$manager->flush();
// ...
$searchResult = $client->execute(
    $manager->getSearchCommand(\MyApp\Model\Product::class)
        ->setQuery((new MacFJA\RediSearch\Query\Builder())->addNumericFacet('price', 0, 15)->render())
        ->setLimit(0, 12)
);
// ...
$suggestions = $manager->getSuggestions(\MyApp\Model\Product::class, 'shoe');
```

## Entity Mapping

There are 5 ways to map a PHP class to RediSearch object.

- With annotations (similar to Doctrine ORM)
- With PHP 8 attributes
- With JSON definition
- With XML definition
- By implementing an interface

### Annotation and Attribute Mapping

A class is considered as valid if it had at least one field mapping

Annotation | PHP 8 Attribute | Scope | Default
--- | --- | --- | ---
`@Index` | `#[Index]` | Class |  Class name (with namespace)
`@DocumentId` | `#[DocumentId]` | Property or Method | Randomly generate value
`@TextField` | `#[TextField]` | Property or Method | _None_
`@NumericField` | `#[NumericField]` | Property or Method | _None_
`@TagField` | `#[TagField]` | Property or Method | _None_
`@GeoField` | `#[GeoField]` | Property or Method | _None_
`@Suggestion` | `#[Suggestion]` | Property or Method | _None_

Annotation and PHP 8 attribute mapping are parsed by respectively `\MacFJA\RediSearch\Integration\Annotation\AnnotationProvider` and `\MacFJA\RediSearch\Integration\Attribute\AttributeProvider`.

#### The `Index` mapping

The `@Index(name, [prefix], [stopsWords])` (or `#[Index(name, [prefix], [stopsWords])]` for PHP 8 attribute) allow you to specify the index where the class will be put.

If the mapping is missing, the Full Class Qualifier Name (namespace + classname) will be used as index name, not prefix will be used and default StopsWorlds will be used.

#### The `DocumentId` mapping

The `@DocumentId` (or `#[DocumentId]` for PHP 8 attribute) allow you to specify which hash should be used to identify the document in Redis.
The mapping can be set on a property, or on a method that can be call without any parameter.

If the mapping is missing, a random hash will be generated.

#### The `TextField` mapping

The `@TextField([name], [noStem], [weight], [phonetic], [sortable], [noIndex], [unNormalized])` (or `#[TextField([name], [noStem], [weight], [phonetic], [sortable], [noIndex], [unNormalized])]` for PHP 8 attribute) allow you to add a text in the search engine.
The mapping can be set on a property, or on a method that can be call without any parameter.

- The `name` parameter is used to specify the name of the data in RediSearch. If missing the property name will be used, or the name of the method base on getter rule (`get`/`is`).
- The `noStem` parameter is a boolean used to indicate if the data should use stemming or not.
- The `weight` parameter is a float used to indicate if the weight the data have in result ordering.
- The `phonetic` parameter is a string used to indicate the language to use for phonetic search.
- The `sortable` parameter is a boolean used to indicate if the data can be used to sort result.
- The `noIndex` parameter is a boolean used to indicate if the data should be searchable or not.
- The `unNormalized` parameter is a boolean used to indicate if the data should be searchable or not.

#### The `NumericField` mapping

The `@NumericField([name], [sortable], [noIndex], [unNormalized])` (or `#[NumericField([name], [sortable], [noIndex], [unNormalized])]` for PHP 8 attribute) allow you to add a number in the search engine.
The mapping can be set on a property, or on a method that can be call without any parameter.

- The `name` parameter is used to specify the name of the data in RediSearch. If missing the property name will be used, or the name of the method base on getter rule (`get`/`is`).
- The `sortable` parameter is a boolean used to indicate if the data can be used to sort result.
- The `noIndex` parameter is a boolean used to indicate if the data should be searchable or not.
- The `unNormalized` parameter is a boolean used to indicate if the data should be searchable or not.

#### The `TagField` mapping

The `@TagField([name], [separator], [sortable], [noIndex], [unNormalized])` (or `#[TagField([name], [separator], [sortable], [noIndex], [unNormalized])]` for PHP 8 attribute) allow you to add a text in the search engine.
The mapping can be set on a property, or on a method that can be call without any parameter.

- The `name` parameter is used to specify the name of the data in RediSearch. If missing the property name will be used, or the name of the method base on getter rule (`get`/`is`).
- The `separator` parameter is a string used to indicate char to use to separate values.
- The `sortable` parameter is a boolean used to indicate if the data can be used to sort result.
- The `noIndex` parameter is a boolean used to indicate if the data should be searchable or not.
- The `unNormalized` parameter is a boolean used to indicate if the data should be searchable or not.

The data link to `TagField` can be a scalar data, or a simple array (one dimension) of scalar

#### The `GeoField` mapping

The `@GeoField([name], [noIndex], [sortable], [unNormalized])` (or `#[GeoField([name], [noIndex], [sortable], [unNormalized])]` for PHP 8 attribute) allow you to add a geographic (coordinate) in the search engine.
The mapping can be set on a property, or on a method that can be call without any parameter.

- The `name` parameter is used to specify the name of the data in RediSearch. If missing the property name will be used, or the name of the method base on getter rule (`get`/`is`).
- The `noIndex` parameter is a boolean used to indicate if the data should be searchable or not.
- The `unNormalized` parameter is a boolean used to indicate if the data should be searchable or not.

#### The `Suggestion` mapping

The `@Suggestion([group], [score], [increment], [payload])` (or `#[GeoField([group], [score], [increment], [payload])]` for PHP 8 attribute) allow you to add a geographic (coordinate) in the search engine.
The mapping can be set on a property, or on a method that can be call without any parameter.

- The `group` parameter is used to specify the name of the suggestion registry in RediSearch. If missing the name is `'suggestion'`.
- The `score` parameter is a float used to indicate _priority_ of the value in the suggestion. If missing the score is set to `1.0`.
- The `increment` parameter is a boolean used to indicate is the score of the current suggestion should be added to an already existing suggestion with the same value. If missing, score are not added.
- The `payload` parameter is a string used to add additional data to the suggestion (not used in the suggestion engine). If missing no payload is attach to the suggestion.

### JSON Mapping

A JSON mapping file can contain several class mapping.
The JSON should respect the [Schema](src/Json/schema.json).

```json
[
  {
    "class": "\\MyApp\\Model\\Product",
    "index": "product",
    "stop-words": ["the", "a", "an", "this"],
    "fields": {
      "name": {"property": "name", "type": "text"},
      "manufacturer": {"getter": "getManufacturerName", "type": "text"},
      "price": {"getter": "getFinalPrice", "type": "numeric"},
      "colors": {"property": "colors", "type": "tag", "separator": "|"},
      "manufacturer_address": {"property": "manufacturerAddress", "type": "geo"}
    },
    "suggestions": [
      {"property": "name"},
      {"property": "colors", "group": "color"},
      {"getter": "getManufacturerName"}
    ]
  }
]
```

To enable JSON mapping you must use a `\MacFJA\RediSearch\Integration\Json\JsonProvider`.
The JSON PHP extension must also be installed.

The JSON file must be given to the `\MacFJA\RediSearch\Integration\Json\JsonProvider::addJson` method.

(The `JsonProvider` can be added to a `CompositeProvider`)

### XML Mapping

A XML mapping file can contain several class mapping.
The JSON should respect the [XSD Schema](src/Xml/schema.xsd).

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<redis-search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://github.com/MacFJA/php-redisearch-integration/blob/main/src/Xml/schema.xsd">
    <class name="MyApp\Model\Integration\Product" indexname="product">
        <id type="property">id</id>
        <stops-words>
            <word>the</word>
            <word>a</word>
            <word>an</word>
            <word>this</word>
        </stops-words>
        <fields>
            <text-field>firstname</text-field>
            <text-field getter="getManufacturerName">firstname</text-field>
            <numeric-field getter="getFinalPrice">price</numeric-field>
            <tag-field property="colors" separator="|">color</tag-field>
            <geo-field property="manufacturerAddress">manufacturer_address</geo-field>
        </fields>
        <suggestions>
            <property name="name" />
            <property name="colors" group="color" />
            <getter name="getManufacturerName" />
        </suggestions>
    </class>
</redis-search>
```

To enable XML mapping you must use a `\MacFJA\RediSearch\Integration\Xml\XmlProvider`.
The SimpleXML PHP extension must also be installed.

The XML file must be given to the `\MacFJA\RediSearch\Integration\Xml\XmlProvider::addXml` method.

(The `XmlProvider` can be added to a `CompositeProvider`)

### The interface mapping

You can create your own mapping by implementing the `\MacFJA\RediSearch\Integration\Mapping` interface, and add the class to the `SimpleProvider`.

(The `SimpleProvider` can be added to a `CompositeProvider`)

## Events

The `ObjectWorker` emit several events to allow you to alter its behavior.

Event have separate into two main group: **Before** and **After** group.
With the **Before** group you can change configurations before interacting with Redis.
The **After** allow you to do more action with results.

### The `Before` group

_In **bold** parameters that can be changed._

Event name | `ObjectWorker` method (Associated Interface) | Available parameters
--- | --- | ---
`AddingDocumentToSearchEvent` | `persist` and `persistSearch` (`ObjectManager`) | **`data`**, **`documentId`**, `instance` _(r/o)_ 
`AddingSuggestionEvent` | `persist` and `persistSuggestions` (`ObjectManager`) | **`suggestionMapping`**, `instance` _(r/o)_ 
`CreatingIndexEvent` | `createIndex` (`ObjectManager`) | **`builder`**, `classname` _(r/o)_ 
`GettingSuggestionsEvent` | `getSuggestions` (`ObjectRepository`) | `classname` _(r/o)_, **`prefix`**, **`fuzzy`**, **`withScores`**, **`withPayloads`**, **`max`**, **`inGroup`** 
`GettingFacetsEvent` | `getFacets` (`ObjectRepository`) | `classname` _(r/o)_, **`query`**, **`fields`** 
`RemovingDocumentFromSearchEvent` | `remove`  (`ObjectManager`) | `instance` _(r/o)_, **`documentId`** 

### The `After` group

_In **bold** parameters that can be changed._

Event name | `ObjectWork` method (Associated Interface) | Available parameters
--- | --- | ---
`AddingDocumentToSearchEvent` | `persist` and `persistSearch` (`ObjectManager`) |  `data` _(r/o)_, `documentId` _(r/o)_, `instance` _(r/o)_, `update` _(r/o)_
`AddingSuggestionEvent` | `persist` and `persistSuggestions` (`ObjectManager`) | `group` _(r/o)_, `suggestion` _(r/o)_, `score` _(r/o)_, `increment` _(r/o)_, `payload` _(r/o)_, `instance` _(r/o)_
`CreatingIndexEvent` | `createIndex` (`ObjectManager`) | `succeed` _(r/o)_, `classname` _(r/o)_
`GettingAggregateEvent` | `getAggregateCommand` (`ObjectRepository`) | **`aggregate`**, `classname` _(r/o)_ 
`GettingFacetsEvent` | `getFacets` (`ObjectRepository`) | `classname` _(r/o)_, `query` _(r/o)_, `fields` _(r/o)_, **`facets`**
`GettingSearchEvent` | `getSearchCommand` (`ObjectRepository`) | **`search`**, `classname` _(r/o)_ 
`GettingSuggestionsEvent` | `getSuggestions` (`ObjectRepository`) | `classname` _(r/o)_, `prefix` _(r/o)_, `fuzzy` _(r/o)_, `withScores` _(r/o)_, `withPayloads` _(r/o)_, `max` _(r/o)_, `inGroup` _(r/o)_, **`suggestions`** 
`RemovingDocumentFromSearchEvent` | `remove` (`ObjectManager`) | `instance` _(r/o)_, `documentId` _(r/o)_, `succeed` _(r/o)_

## Contributing

You can contribute to the library.
To do so, you have Github issues to:
 - ask your questions
 - suggest new mapping provider
 - request any change (typo, bad code, etc.)
 - and much more...

You also have PR to:
 - add a new mapping provider
 - suggest a correction
 - and much more... 

See [CONTRIBUTING](CONTRIBUTING.md) for more information.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
