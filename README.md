# Propel3

Propel3 is an open-source Object-Relational Mapping (ORM) for modern PHP 7.1+.

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![Test Coverage](https://api.codeclimate.com/v1/badges/97c839fc8e745f9f8f60/test_coverage)](https://codeclimate.com/github/propelorm/Propel3/test_coverage)
[![Maintainability](https://api.codeclimate.com/v1/badges/97c839fc8e745f9f8f60/maintainability)](https://codeclimate.com/github/propelorm/Propel3/maintainability)
[![PPM Compatible](https://raw.githubusercontent.com/php-pm/ppm-badge/master/ppm-badge.png)](https://github.com/php-pm/php-pm)
[![Gitter](https://badges.gitter.im/propelorm/Propel.svg)](https://gitter.im/propelorm/Propel)

Propel3 introduces a data-mapper implementation which separates your entities from the actual persisting logic.

## Status

This is in current development and is not yet ready to use (please see the [TODO](TODO.md) list).

## Features

 - Propel is blazing fast
 - Data mapper with runtime UnitOfWork for high performance with massive object counts (bulks inserts/updates)
 - Query-Builder
 - Very IDE friendly thanks to code-generation
 - Generation of methods for all columns and relations
 - Database schema migration
 - Schema reverse engineering
 - Customizable
 - Propel comes with common ‘behaviors’
 - Completely unit tested for MySQL, PostgreSQL, SQLite. Oracle and MSSQL are experimental.

## Example

### Define the entity

##### XML

```xml
<database name="default">
  <entity name="Vendor\Car">
      <field name="id" primaryKey="true" autoIncrement="true" type="INTEGER" />
      <field name="name" type="VARCHAR" required="true"  />
      <relation target="Publisher" onDelete="setnull"/>
      <relation target="Author" onDelete="setnull" onUpdate="cascade"/>
  </entity>
</database>
```

##### or annotations

In work.

```php

namespace Vendor

use Propel\Annotations\Entity;
use Propel\Annotations\Field;
use Propel\Annotations\PrimaryKey;

/**
 * @Entity()
 */ 
class Car
{
    /**
     * @PrimaryKey(auto_increment=true)
     */
    private $id;
    
    /**
     * @Field(type="VARCHAR")
     */
    private $name;
    
    // getters/setters
}
```

### Data mapper

```php
$propel = new Propel\Runtime\Configuration('path/to/propel.yml');

// require a session for each request/workload
$session = $propel->getSession();

$car = new Vendor\Car();
$car->setName('Ford');

$session->persist($car);
$session->commit();
```

### RAD/Active-record

```php
// use <entity name="Vendor\Car" activeRecord="true">
$car = new Vendor\Car();
$car->setName('Ford');
$car->save();
```

## Installation

Read the [Propel documentation](http://www.propelorm.org/). This documentation is for Propel2 still. 

## Contribute

Everybody can contribute to Propel. Just fork it, and send Pull Requests.
You have to follow [PSR2 coding standards](http://www.php-fig.org/psr/psr-2/) and provides unit tests as much as possible.

Please see our [contribution guideline](http://propelorm.org/contribute.html). Thank you!

## License

See the `LICENSE` file.
