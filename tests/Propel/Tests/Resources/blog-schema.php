<?php

use Propel\Generator\Model\Schema;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Vendor;

/** @var Database $database */
$database = include __DIR__ . DIRECTORY_SEPARATOR . 'blog-database.php';

$schema = new Schema();
$schema->setName('acme');
$schema->addDatabase($database);
$schema->setPlatform($database->getPlatform());
$schema->getDatabase()->getVendorByType('mysql')->setParameter(Vendor::MYSQL_ENGINE, 'MyISAM');

return $schema;
