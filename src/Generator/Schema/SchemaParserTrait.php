<?php
namespace Propel\Generator\Schema;

use phootwork\file\File;
use Propel\Generator\Manager\BehaviorManager;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Model;
use Propel\Generator\Model\ModelFactory;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Schema;
use Propel\Generator\Model\Unique;

/**
 * A parser trait that visits elements on a schema array
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 * @author Thomas Gossmann
 * @author Cristiano Cinotti
 *
 */
trait SchemaParserTrait
{
    /**
     * @var ModelFactory
     */
    private $modelFactory;

    private function getModelFactory(): ModelFactory
    {
        if (null === $this->modelFactory) {
            $this->modelFactory = new ModelFactory($this->getGeneratorConfig());
        }

        return $this->modelFactory;
    }

    /**
     * @param array $schemaContent
     * @param Schema $schema
     */
    private function parseDatabase(array $schemaContent, Schema $schema): void
    {
        $database = $this->getModelFactory()->createDatabase($schemaContent);
        $schema->addDatabase($database);

        $this->addExternalSchemas($schemaContent['external-schemas'], $schema);
        $this->addBehaviors($schemaContent['behaviors'], $database);
        $this->addVendor($schemaContent, $database);
        $this->addEntities($schemaContent['entities'], $database);
    }

    /**
     * @param array $entities
     * @param Database $database
     */
    private function addEntities(array $entities, Database $database): void
    {
        foreach ($entities as $entity) {
            $entityObj = $this->getModelFactory()->createEntity($entity);
            $database->addEntity($entityObj);

            if ($database->getSchema()->isExternalSchema()) {
                $entity->setForReferenceOnly(true);
            }

            $this->addFields($entity['fields'], $entityObj);
            $this->addRelations($entity['relations'], $entityObj);
            $this->addIndices($entity['indices'], $entityObj);
            $this->addUniques($entity['uniques'], $entityObj);
            $this->addBehaviors($entity['behaviors'], $entityObj);
            $this->addVendor($entity, $entityObj);
            $this->addIdMethodParameter($entity, $entityObj);
        }
    }

    /**
     * @param array $fields
     * @param Entity $entity
     */
    private function addFields(array $fields, Entity $entity): void
    {
        foreach ($fields as $field) {
            $fieldObj = $this->getModelFactory()->createField($field);
            $this->addInheritance($field['inheritances'], $fieldObj);
            $this->addVendor($field, $fieldObj);

            $entity->addField($fieldObj);
        }
    }

    /**
     * @param array $relations
     * @param Entity $entity
     */
    private function addRelations(array $relations, Entity $entity): void
    {
        if (count($relations) <= 0) {
            return;
        }

        foreach ($relations as $relation) {
            $relationObj = $this->getModelFactory()->createRelation($relation);
            $this->addVendor($relation, $relationObj);
            $entity->addRelation($relationObj);
        }
    }

    /**
     * @param array $indices
     * @param Entity $entity
     */
    private function addIndices(array $indices, Entity $entity): void
    {
        if (count($indices) <= 0) {
            return;
        }

        foreach ($indices as $index) {
            $indexObj = $this->getModelFactory()->createIndex($index);
            foreach ($index['index-fields'] as $indexField) {
                $field = $entity->getField($indexField['name']);
                if (isset($indexField['size'])) {
                    $index->getFieldSizes()->set($field->getName(), $indexField['size']);
                }
                $indexObj->addField($field);
            }
            $this->addVendor($index, $indexObj);
            $entity->addIndex($indexObj);
        }
    }

    /**
     * @param array $uniques
     * @param Entity $entity
     */
    private function addUniques(array $uniques, Entity $entity): void
    {
        if (count($uniques) <= 0) {
            return;
        }

        foreach ($uniques as $unique) {
            $uniqueObj = $this->getModelFactory()->createUnique($unique);
            foreach ($unique['unique-fields'] as $uniqueField) {
                $field = $entity->getField($uniqueField['name']);
                if (isset($uniqueField['size'])) {
                    $field->setSize($uniqueField['size']);
                }
                $uniqueObj->addField($field);
            }
            $this->addVendor($unique, $uniqueObj);
            $entity->addUnique($uniqueObj);
        }
    }

    /**
     * @param array $externalSchemas
     * @param Schema $schema
     */
    private function addExternalSchemas(array $externalSchemas, Schema $schema): void
    {
        if (count($externalSchemas) <= 0) {
            return;
        }

        foreach ($externalSchemas as $externalSchema) {
            $filename = $this->getExternalFilename($externalSchema['filename'], $schema);
            /** @var Schema $extSchema */
            $extSchema = $this->parse($filename);
            $extSchema->setReferenceOnly($externalSchema['referenceOnly']);
            $schema->addExternalSchema($extSchema);
        }
    }

    /**
     * @param array $behaviors
     * @param Database|Entity $parent
     */
    private function addBehaviors(array $behaviors, $parent): void
    {
        if (count($behaviors) <= 0) {
            return;
        }

        foreach ($behaviors as $id => $behavior) {
            $behaviorObj = $this->getModelFactory()->createBehavior($behavior);
            $behaviorObj->setId($id);
            $parent->addBehavior($behaviorObj);
        }
    }

    /**
     * @param array $parent
     * @param Database|Entity|Field|Index|Unique|Relation $parentObj
     */
    private function addVendor(array $parent, $parentObj): void
    {
        if (!isset($parent['vendor'])) {
            return;
        }

        $obj = $this->getModelFactory()->createVendor($parent['vendor']);
        $parentObj->addVendor($obj);
    }

    /**
     * @param array $inheritances
     * @param Field $field
     */
    private function addInheritance(array $inheritances, Field $field): void
    {
        if (count($inheritances) <= 0) {
            return;
        }

        foreach ($inheritances as $inheritance) {
            $inheritObj = $this->getModelFactory()->createInheritance($inheritance);
            $field->addInheritance($inheritObj);
        }
    }

    /**
     * @param array $attributes
     * @param Entity $entity
     */
    private function addIdMethodParameter(array $attributes, Entity $entity): void
    {
        if (!isset($attributes['id_method_parameter'])) {
            return;
        }

        $idObj = $this->getModelFactory()->createIdMethodParameter($attributes['id_method_parameter']);
        $entity->addIdMethodParameter($idObj);
    }

    /**
     * If the external schema filename is not an absolute path,
     * make it relative to the current schema directory.
     *
     * @param string $filename
     * @param Schema $schema
     *
     * @return string
     */
    private function getExternalFilename(string $filename, Schema $schema): string
    {
        $file = new File($filename);
        if (!$file->toPath()->isAbsolute()) {
            $schemaFile = new File($schema->getFilename());

            return $schemaFile->getDirname() . DIRECTORY_SEPARATOR . $file->getPathname();
        }

        return $file->getPathname();
    }
}
