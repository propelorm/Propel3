<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Tests\Generator\Schema;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Propel\Generator\Schema\Loader\SchemaLoader;
use Propel\Tests\TestCase;

class ReaderTestCase extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    protected $root;

    /**
     * @var SchemaLoader
     */
    protected $loader;

    public function setUp()
    {
        $this->root = vfsStream::setup('schema_dir');
    }

    protected function addJsonSchema(): void
    {
        $content = <<<EOF
{
  "database": {
    "name": "bookstore",
    "defaultIdMethod": "native",
    "namespace": "Propel\\\Tests\\\Bookstore",
    "activeRecord": "true",
    "entities": [
      {
        "name": "Book",
        "fields": [
          {
            "name": "id",
            "required": "true",
            "primaryKey": "true",
            "autoIncrement": "true",
            "type": "INTEGER"
          },
          {
            "name": "title",
            "type": "VARCHAR",
            "required": "true",
            "primaryString": "true"
          },
          {
            "name": "ISBN",
            "required": "true",
            "type": "VARCHAR",
            "size": "24"
          },
          {
            "name": "price",
            "required": "false",
            "type": "FLOAT"
          }
        ],
        "relations": [
          {
            "target": "Publisher",
            "onDelete": "setnull"
          },
          {
            "target": "Author",
            "onDelete": "setnull",
            "onUpdate": "cascade"
          }
        ]
      },
      {
        "name": "Publisher",
        "defaultStringFormat": "XML",
        "fields": [
          {
            "name": "id",
            "required": "true",
            "primaryKey": "true",
            "autoIncrement": "true",
            "type": "INTEGER"
          },
          {
            "name": "name",
            "required": "true",
            "type": "VARCHAR",
            "size": "128",
            "default": "Penguin"
          }
        ]
      },
      {
        "name": "Author",
        "fields": [
          {
            "name": "id",
            "required": "true",
            "primaryKey": "true",
            "autoIncrement": "true",
            "type": "INTEGER"
          },
          {
            "name": "firstName",
            "required": "true",
            "type": "VARCHAR",
            "size": "128"
          },
          {
            "name": "lastName",
            "required": "true",
            "type": "VARCHAR",
            "size": "128"
          },
          {
            "name": "email",
            "type": "VARCHAR",
            "size": "128"
          }
        ]
      }
    ]
  }
}
EOF;
        vfsStream::newFile('schema.json')->at($this->root)->setContent($content);
    }

    protected function addPhpSchema(): void
    {
        $content = <<<EOF
<?php

return [
  "database" => [
    "name" => "bookstore",
    "defaultIdMethod" => "native",
    "namespace" => "Propel\Tests\Bookstore",
    "activeRecord" => true,
    "entities" => [
      [
        "name" => "Book",
        "fields" => [
          [
            "name" => "id",
            "required" => true,
            "primaryKey" => true,
            "autoIncrement" => true,
            "type" => "INTEGER"
          ],
          [
            "name" => "title",
            "type" => "VARCHAR",
            "required" => true,
            "primaryString" => true
          ],
          [
            "name" => "ISBN",
            "required" => true,
            "type" => "VARCHAR",
            "size" => 24
          ],
          [
            "name" => "price",
            "required" => false,
            "type" => "FLOAT"
          ]
        ],
        "relations" => [
          [
            "target" => "Publisher",
            "onDelete" => "setnull"
          ],
          [
            "target" => "Author",
            "onDelete" => "setnull",
            "onUpdate" => "cascade"
          ]
        ]
      ],
      [
        "name" => "Publisher",
        "defaultStringFormat" => "XML",
        "fields" => [
          [
            "name" => "id",
            "required" => true,
            "primaryKey" => true,
            "autoIncrement" => true,
            "type" => "INTEGER"
          ],
          [
            "name" => "name",
            "required" => true,
            "type" => "VARCHAR",
            "size" => 128,
            "default" => "Penguin"
          ]
        ]
      ],
      [
        "name" => "Author",
        "fields" => [
          [
            "name" => "id",
            "required" => true,
            "primaryKey" => true,
            "autoIncrement" => true,
            "type" => "INTEGER"
          ],
          [
            "name" => "firstName",
            "required" => true,
            "type" => "VARCHAR",
            "size" => 128
          ],
          [
            "name" => "lastName",
            "required" => true,
            "type" => "VARCHAR",
            "size" => 128
          ],
          [
            "name" => "email",
            "type" => "VARCHAR",
            "size" => 128
          ]
        ]
      ]
    ]
  ]
];
EOF;
        vfsStream::newFile('schema.php')->at($this->root)->setContent($content);
    }

    protected function addXmlSchema(): void
    {
        $content = <<<XML
<database name="bookstore" defaultIdMethod="native" namespace="Propel\Tests\Bookstore" activeRecord="true">
    <entity name="Book" description="Book Table">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Book Id"/>
        <field name="title" type="VARCHAR" required="true" description="Book Title" primaryString="true"/>
        <field name="ISBN" required="true" type="VARCHAR" size="24" description="ISBN Number" primaryString="false"/>
        <field name="price" required="false" type="FLOAT" description="Price of the book."/>
        <relation target="Publisher" onDelete="setnull"/>
        <relation target="Author" onDelete="setnull" onUpdate="cascade"/>
    </entity>

    <entity name="Publisher" description="Publisher Table" defaultStringFormat="XML">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"
               description="Publisher Id"/>
        <field name="name" required="true" type="VARCHAR" size="128" default="Penguin" description="Publisher Name"/>
    </entity>

    <entity name="Author" description="Author Table">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Author Id"/>
        <field name="firstName" required="true" type="VARCHAR" size="128" description="First Name"/>
        <field name="lastName" required="true" type="VARCHAR" size="128" description="Last Name"/>
        <field name="email" type="VARCHAR" size="128" description="E-Mail Address"/>
    </entity>
</database>
XML;

        vfsStream::newFile('schema.xml')->at($this->root)->setContent($content);
    }

    protected function addYamlSchema()
    {
        $content = <<<YML
database:
    name: bookstore
    defaultIdMethod: native
    namespace: Propel\Tests\Bookstore
    activeRecord: true
    
    entities:
        Book:
            name: Book
            description: Book Table
            fields:
                id:
                    name: id
                    required: true
                    primaryKey: true
                    autoIncrement: true
                    type: INTEGER
                title:
                    name: title
                    type: VARCHAR
                    required: true
                    primaryString: true
                ISBN:
                    name: ISBN
                    required: true
                    type: VARCHAR
                    size: 24
                price:
                    name: price
                    required: false
                    type: FLOAT
            relations:
                - {target: Publisher, onDelete: setnull}
                - {target: Author, onDelete: setnull, onUpdate: cascade}
        Publisher:
            name: Publisher
            defaultStringFormat: XML
            fields:
                id:
                    name: id
                    required: true
                    primaryKey: true
                    autoIncrement: true
                    type: INTEGER
                name:
                    name: name
                    required: true
                    type: VARCHAR
                    size: 128
                    default: Penguin
        Author:
            name: Author
            fields:
                id:
                    name: id
                    required: true
                    primaryKey: true
                    autoIncrement: true
                    type: INTEGER
                firstName:
                    name: firstName
                    required: true
                    type: VARCHAR
                    size: 128
                lastName:
                    name: lastName
                    required: true
                    type: VARCHAR
                    size: 128
                email:
                    name: email
                    type: VARCHAR
                    size: 128
YML;

        vfsStream::newFile('schema.yaml')->at($this->root)->setContent($content);
    }
}
