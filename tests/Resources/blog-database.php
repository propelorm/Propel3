<?php

use Propel\Generator\Behavior\Sluggable\SluggableBehavior;
use Propel\Generator\Behavior\Timestampable\TimestampableBehavior;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\Vendor;
use Propel\Generator\Platform\MysqlPlatform;

/* Fields */
$field11 = new Field('id', PropelTypes::INTEGER, 7);
$field11->setAutoIncrement();
$field11->setNotNull();
$field11->setPrimaryKey();
$field12 = new Field('authorId', PropelTypes::SMALLINT, 3);
$field12->setNotNull();
$field13 = new Field('categoryId', PropelTypes::TINYINT, 2);
$field13->setNotNull();
$field14 = new Field('title', PropelTypes::VARCHAR, 100);
$field14->setNotNull();
$field15 = new Field('body', PropelTypes::CLOB);
$field16 = new Field('averageRating', PropelTypes::FLOAT, 2);
$field16->setScale(2);
$field16->setDescription('The post rating in percentage');

$field21 = new Field('id', PropelTypes::SMALLINT, 3);
$field21->setAutoIncrement();
$field21->setNotNull();
$field21->setPrimaryKey();
$field22 = new Field('username', PropelTypes::VARCHAR, 15);
$field22->setNotNull();
$field23 = new Field('password', PropelTypes::VARCHAR, 40);
$field23->setNotNull();

$field31 = new Field('id', PropelTypes::TINYINT, 2);
$field31->setAutoIncrement();
$field31->setNotNull();
$field31->setPrimaryKey();
$field32 = new Field('name', PropelTypes::VARCHAR, 40);
$field32->setNotNull();

$field41 = new Field('id', PropelTypes::INTEGER, 7);
$field41->setAutoIncrement();
$field41->setNotNull();
$field41->setPrimaryKey();
$field42 = new Field('name', PropelTypes::VARCHAR, 40);
$field42->setNotNull();

$field51 = new Field('postId', PropelTypes::INTEGER, 7);
$field51->setNotNull();
$field51->setPrimaryKey();
$field52 = new Field('tagId', PropelTypes::INTEGER, 7);
$field52->setNotNull();
$field52->setPrimaryKey();

$field61 = new Field('id', PropelTypes::INTEGER, 5);
$field61->setNotNull();
$field61->setAutoIncrement();
$field61->setPrimaryKey();
$field62 = new Field('title', PropelTypes::VARCHAR, 150);
$field62->setNotNull();
$field63 = new Field('content', PropelTypes::CLOB);
$field63->addVendor(new Vendor('mysql', [
    Vendor::MYSQL_CHARSET => 'latin1',
    Vendor::MYSQL_COLLATE => 'latin1_general_ci',
]));
$field64 = new Field('isPublished', PropelTypes::BOOLEAN);
$field64->setNotNull();
$field64->setDefaultValue('false');

/* Foreign Keys */
$fkAuthorPost = new Relation('fk_post_has_author');
$fkAuthorPost->addReference('authorId', 'id');
$fkAuthorPost->setForeignEntityName('BlogAuthor');
$fkAuthorPost->setRefField('posts');
$fkAuthorPost->setField('author');
$fkAuthorPost->setDefaultJoin('Criteria::LEFT_JOIN');
$fkAuthorPost->setOnDelete('CASCADE');

$fkCategoryPost = new Relation('fk_post_has_category');
$fkCategoryPost->addReference('categoryId', 'id');
$fkCategoryPost->setForeignEntityName('BlogCategory');
$fkCategoryPost->setRefField('posts');
$fkCategoryPost->setField('category');
$fkCategoryPost->setDefaultJoin('Criteria::INNER_JOIN');
$fkCategoryPost->setOnDelete('SETNULL');

$fkPostTag = new Relation('fk_post_has_tags');
$fkPostTag->addReference('postId', 'id');
$fkPostTag->setForeignEntityName('BlogPost');
$fkPostTag->setField('post');
$fkPostTag->setDefaultJoin('Criteria::LEFT_JOIN');
$fkPostTag->setOnDelete('CASCADE');

$fkTagPost = new Relation('fk_tag_has_posts');
$fkTagPost->addReference('tagId', 'id');
$fkTagPost->setForeignEntityName('BlogTag');
$fkTagPost->setField('tag');
$fkTagPost->setDefaultJoin('Criteria::LEFT_JOIN');
$fkTagPost->setOnDelete('CASCADE');

/* Regular Indexes */
$pageContentFulltextIdx = new Index('page_content_fulltext_idx');
$pageContentFulltextIdx->addField($field63);
$pageContentFulltextIdx->addVendor(new Vendor('mysql', [Vendor::MYSQL_INDEX_TYPE => 'FULLTEXT']));

/* Unique Indexes */
$authorUsernameUnique = new Unique('author_password_unique_idx');
$authorUsernameUnique->addField($field22);
$authorUsernameUnique->getFieldSizes()->set($field22->getName(), 8);

/* Behaviors */
$timestampableBehavior = new TimestampableBehavior();
$timestampableBehavior->setName('timestampable');
$sluggableBehavior = new SluggableBehavior();
$sluggableBehavior->setName('sluggable');
$sluggableBehavior->setParameter('slug_pattern', '/posts/{Title}');

/* Entities */
$entity1 = new Entity('BlogPost');
$entity1->setDescription('The list of posts');
$entity1->setNamespace('Blog');
$entity1->addFields([ $field11, $field12, $field13, $field14, $field15, $field16 ]);
$entity1->addRelations([ $fkAuthorPost, $fkCategoryPost ]);
$entity1->addBehavior($timestampableBehavior);
$entity1->addBehavior($sluggableBehavior);

$entity2 = new Entity('BlogAuthor');
$entity2->setDescription('The list of authors');
$entity2->setNamespace('Blog');
$entity2->addFields([ $field21, $field22, $field23 ]);
$entity2->addUnique($authorUsernameUnique);

$entity3 = new Entity('BlogCategory');
$entity3->setDescription('The list of categories');
$entity3->setNamespace('Blog');
$entity3->addFields([ $field31, $field32 ]);

$entity4 = new Entity('BlogTag');
$entity4->setDescription('The list of tags');
$entity4->setNamespace('Blog');
$entity4->addFields([ $field41, $field42 ]);

$entity5 = new Entity('BlogPostTag');
$entity5->setNamespace('Blog');
$entity5->setCrossRef();
$entity5->addFields([ $field51, $field52 ]);
$entity5->addRelations([ $fkPostTag, $fkTagPost ]);

$entity6 = new Entity('CmsPage');
$entity6->setName('Page');
$entity6->setTableName('cms_page');
$entity6->setNamespace('Cms');
$entity6->addFields([ $field61, $field62, $field63, $field64 ]);
$entity6->addIndex($pageContentFulltextIdx);
$entity6->addVendor(new Vendor('mysql', [Vendor::MYSQL_ENGINE => 'MyISAM']));

/* Database */
$database = new Database('acme_blog', new MysqlPlatform());
$database->setSchemaName('acme');
$database->setNamespace('Acme\\Model');
$database->setHeavyIndexing();
$database->addVendor(new Vendor('mysql', [ 'Engine' => 'InnoDB', 'Charset' => 'utf8' ]));
$database->addEntities([ $entity1, $entity2, $entity3, $entity4, $entity5, $entity6 ]);

return $database;
