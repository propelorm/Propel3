<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

declare(strict_types=1);

namespace Propel\Generator\Platform;

use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Model;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Schema;

/**
 * Trait FinalizationTrait
 *
 * Methods to perform the final initialization of model objects
 *
 */
trait FinalizationTrait
{
    /**
     * Do final initialization of the whole schema.
     *
     * @param Schema $schema
     */
    public function doFinalInitialization(Schema $schema)
    {
        foreach ($schema->getDatabases() as $database) {
            // execute database behaviors
            foreach ($database->getBehaviors() as $behavior) {
                $behavior->modifyDatabase();
            }
            // execute entity behaviors (may add new entities and new behaviors)
            while ($behavior = $database->getNextEntityBehavior()) {
                $behavior->getEntityModifier()->modifyEntity();
                $behavior->setEntityModified(true);
            }

            $this->finalizeDefinition($database);
        }
    }

    /**
     * Finalize this entity.
     *
     * @param Database $database
     */
    public function finalizeDefinition(Database $database)
    {
        foreach ($database->getEntities() as $entity) {

            // Heavy indexing must wait until after all columns composing
            // a entity's primary key have been parsed.
            if ($entity->isHeavyIndexing()) {
                $this->doHeavyIndexing($entity);
            }

            // if idMethod is "native" and in fact there are no autoIncrement
            // columns in the entity, then change it to "none"
            $anyAutoInc = false;
            foreach ($entity->getFields() as $column) {
                if ($column->isAutoIncrement()) {
                    $anyAutoInc = true;
                }
            }

            if (Model::ID_METHOD_NATIVE === $entity->getIdMethod() && !$anyAutoInc) {
                $entity->setIdMethod(Model::ID_METHOD_NONE);
            }

            $this->setupRelationReferences($entity);
            $this->setupReferrers($entity);

            //MyISAM engine doesn't create foreign key indices automatically
            if ($this instanceof MysqlPlatform) {
                if ('MyISAM' === $this->getMysqlEntityType($entity)) {
                    $this->addExtraIndices($entity);
                }
            }
        }
    }

    /**
     * Browses the foreign keys and creates referrers for the foreign entity.
     * This method can be called several times on the same entity. It only
     * adds the missing referrers and is non-destructive.
     * Warning: only use when all the entities were created.
     *
     * @param  Entity $entity
     *
     * @throws BuildException
     */
    protected function setupReferrers(Entity $entity)
    {
        foreach ($entity->getRelations() as $relation) {
            $this->setupReferrer($relation);
        }
    }

    /**
     * @param Relation $relation
     */
    protected function setupReferrer(Relation $relation)
    {
        $entity = $relation->getEntity();
        // entity referrers
        $hasEntity = $entity->getDatabase()->hasEntityByName($relation->getForeignEntityName()) ?
            true :
            $entity->getDatabase()->hasEntityByFullName($relation->getForeignEntityName())
        ;
        if (!$hasEntity) {
            throw new BuildException(
                sprintf(
                    'Entity "%s" contains a relation to nonexistent entity "%s". [%s]',
                    $entity->getName(),
                    $relation->getForeignEntityName(),
                    implode(', ', $entity->getDatabase()->getEntityNames())
                )
            );
        }

        $foreignEntity = $entity->getDatabase()->getEntityByName($relation->getForeignEntityName()) ??
            $entity->getDatabase()->getEntityByFullName($relation->getForeignEntityName())
        ;
        $referrers = $foreignEntity->getReferrers();
        if (null === $referrers || !$referrers->contains($relation)) {
            $foreignEntity->addReferrer($relation);
        }

        // foreign pk's
        $localFieldNames = $relation->getLocalFields();
        foreach ($localFieldNames as $localFieldName) {
            $localField = $entity->getFieldByName($localFieldName);
            if (null !== $localField) {
                if ($localField->isPrimaryKey() && !$entity->getContainsForeignPK()) {
                    $entity->setContainsForeignPK(true);
                }

                continue;
            }

            throw new BuildException(
                sprintf(
                    'Entity "%s" contains a foreign key with nonexistent local field "%s"',
                    $entity->getName(),
                    $localFieldName
                )
            );
        }

        // foreign field references
        $foreignFields = $relation->getForeignFieldObjects();
        foreach ($foreignFields as $foreignField) {
            if (null === $foreignEntity) {
                continue;
            }
            if (null !== $foreignField) {
                if (!$foreignField->hasReferrer($relation)) {
                    $foreignField->addReferrer($relation);
                }

                continue;
            }
            // if the foreign field does not exist, we may have an
            // external reference or a misspelling
            throw new BuildException(
                sprintf(
                    'Entity "%s" contains a foreign key to entity "%s" with nonexistent field "%s"',
                    $entity->getName(),
                    $foreignEntity->getName(),
                    $foreignField->getName()
                )
            );
        }
    }

    /**
     * @param Entity $entity
     */
    protected function setupRelationReferences(Entity $entity)
    {
        foreach ($entity->getRelations() as $relation) {
            if ($relation->getField()) {
                $relationName = $relation->getField();
            } else {
                $relationName = $relation->getForeignEntityName();
            }

            if (!$relation->getLocalFieldObjects()) {
                //no references defined: set it
                $pks = $relation->getForeignEntity()->getPrimaryKey();
                if (!$pks) {
                    throw new BuildException(sprintf(
                        'Can not set up relation references since target entity `%s` has no primary keys.',
                        $relation->getForeignEntity()->getName()
                    ));
                }

                foreach ($pks as $pk) {
                    $localFieldName = lcfirst($relationName) . ucfirst($pk->getName());
                    $field = new Field();
                    $field->setName($localFieldName);
                    $field->setType($pk->getType());
                    $field->setDomain($pk->getDomain());
                    $field->setImplementationDetail(true);

                    if ($entity->hasField($localFieldName)) {
                        throw new BuildException(sprintf(
                            'Unable to setup automatic relation from %s to %s due to no unique field name. Please specify <relation field="here"> a name'
                        ), $entity->getName(), $relation->getForeignEntity()->getName());
                    }
                    $entity->addField($field);

                    $relation->addReference($localFieldName, $pk->getName());
                }
            } else {
                //we have references, make sure all those columns are marked as implementationDetail
                if ($relation->isLocalPrimaryKey()) {
                    //one-to-one relation are not marked as implementation detail
                    continue;
                }

                foreach ($relation->getFieldObjectsMapArray() as $fields) {
                    /** @var Field $local */
                    /** @var Field $foreign */
                    list($local, $foreign) = $fields;
                    if ($local->isPrimaryKey() && !$foreign->isPrimaryKey()) {
                        $foreign->setImplementationDetail(true);
                    }
                }

                foreach ($relation->getLocalFieldObjects() as $field) {
                    $field->setImplementationDetail(true);
                }
            }
        }
    }

    /**
     * Adds extra indices for multi-part primary key columns.
     *
     * For databases like MySQL, values in a where clause much
     * match key part order from the left to right. So, in the key
     * definition <code>PRIMARY KEY (FOO_ID, BAR_ID)</code>,
     * <code>FOO_ID</code> <i>must</i> be the first element used in
     * the <code>where</code> clause of the SQL query used against
     * this entity for the primary key index to be used. This feature
     * could cause problems under MySQL with heavily indexed entitys,
     * as MySQL currently only supports 16 indices per entity (i.e. it
     * might cause too many indices to be created).
     *
     * See the mysql manual http://www.mysql.com/doc/E/X/EXPLAIN.html
     * for a better description of why heavy indexing is useful for
     * quickly searchable database entities.
     *
     * @param Entity $entity
     */
    protected function doHeavyIndexing(Entity $entity)
    {
        $pk = $entity->getPrimaryKey();
        $size = count($pk);

        // We start at an offset of 1 because the entire column
        // list is generally implicitly indexed by the fact that
        // it's a primary key.
        for ($i = 1; $i < $size; $i++) {
            $idx = new Index();
            $idx->addFields(array_slice($pk, $i, $size));
            $entity->addIndex($idx);
        }
    }
}
