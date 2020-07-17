<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use phootwork\collection\ArrayList;
use phootwork\collection\Set;
use phootwork\lang\Text;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Parts\DatabasePart;
use Propel\Generator\Model\Parts\EntityPart;
use Propel\Generator\Model\Parts\NamePart;
use Propel\Generator\Model\Parts\SuperordinatePart;
use Propel\Generator\Model\Parts\VendorPart;

/**
 * A class for information about table foreign keys.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Fedor <fedor.karpelevitch@home.com>
 * @author Daniel Rall <dlr@finemaltcoding.com>
 * @author Ulf Hermann <ulfhermann@kulturserver.de>
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Relation
{
    use DatabasePart, EntityPart, NamePart, SuperordinatePart, VendorPart;

    private string $foreignEntityName ='';

    /**
     * If foreignEntityName is not given getForeignEntity() uses this entity directly.
     *
     * @var Entity|null
     */
    private ?Entity $foreignEntity;

    private string $field;
    private string $refField;
    private string $refName;
    private string $defaultJoin = 'INNER JOIN';
    private string $onUpdate = Model::RELATION_NONE;
    private string $onDelete = Model::RELATION_NONE;
    private ArrayList $localFields;
    private ArrayList $foreignFields;
    private bool $skipSql = false;
    private bool $skipCodeGeneration = false;
    private bool $autoNaming = false;
    private string $foreignSchema;

    /**
     * Constructs a new Relation object.
     *
     * @param string $name
     */
    public function __construct(string $name = '')
    {
        $this->setName($name);

        $this->localFields = new ArrayList();
        $this->foreignFields = new ArrayList();
        $this->initVendor();
    }

    /**
     * @inheritdoc
     */
    public function getSuperordinate(): Entity
    {
        return $this->getEntity();
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field ?? $this->getName()->toString();
    }

    /**
     * @param string $field
     */
    public function setField(string $field): void
    {
        $this->field = $field;
    }

    /**
     * @return null|string
     */
    public function getRefField(): ?string
    {
        return $this->refField ?? null;
    }

    /**
     * @param string $refField
     */
    public function setRefField(string $refField): void
    {
        $this->refField = $refField;
    }

    /**
     * Returns the normalized input of onDelete and onUpdate behaviors.
     *
     * @param  string $behavior
     *
     * @return string
     */
    public function normalizeFKey(?string $behavior): string
    {
        if (null === $behavior) {
            return Model::RELATION_NONE;
        }

        $behavior = strtoupper($behavior);

        if ('NONE' === $behavior) {
            return Model::RELATION_NONE;
        }

        if ('SETNULL' === $behavior) {
            return Model::RELATION_SETNULL;
        }

        return $behavior;
    }

    /**
     * Returns whether or not the onUpdate behavior is set.
     *
     * @return boolean
     */
    public function hasOnUpdate(): bool
    {
        return Model::RELATION_NONE !== $this->onUpdate;
    }

    /**
     * Returns whether or not the onDelete behavior is set.
     *
     * @return boolean
     */
    public function hasOnDelete(): bool
    {
        return Model::RELATION_NONE !== $this->onDelete;
    }

    /**
     * @return boolean
     */
    public function isSkipCodeGeneration(): bool
    {
        return $this->skipCodeGeneration;
    }

    /**
     * @param boolean $skipCodeGeneration
     */
    public function setSkipCodeGeneration(bool $skipCodeGeneration)
    {
        $this->skipCodeGeneration = $skipCodeGeneration;
    }

    /**
     * Returns true if $field is in our local fields list.
     *
     * @param  Field $field
     *
     * @return boolean
     */
    public function hasLocalField(Field $field): bool
    {
        if ($field = $this->getEntity()->getFieldByName($field->getName()->toString())) {
            return $this->localFields->search($field->getName()->toString(),
                fn(string $element, string $query): bool => $element === $query
            );
        }

        return false;
    }

    /**
     * Returns the onUpdate behavior.
     *
     * @return string
     */
    public function getOnUpdate(): string
    {
        return $this->onUpdate;
    }

    /**
     * Returns the onDelete behavior.
     *
     * @return string
     */
    public function getOnDelete(): string
    {
        return $this->onDelete;
    }

    /**
     * Sets the onDelete behavior.
     *
     * @param string $behavior
     */
    public function setOnDelete(string $behavior)
    {
        $this->onDelete = $this->normalizeFKey($behavior);
    }

    /**
     * Sets the onUpdate behavior.
     *
     * @param string $behavior
     */
    public function setOnUpdate(string $behavior)
    {
        $this->onUpdate = $this->normalizeFKey($behavior);
    }

    /**
     * Returns the foreign key name.
     *
     * @return Text
     */
    public function getName(): Text
    {
        $this->doNaming();

        return $this->name;
    }

    /**
     * @return bool
     */
    public function hasName(): bool
    {
        return !$this->getName()->isEmpty() && !$this->autoNaming;
    }

    /**
     * Sets the foreign key name.
     *
     * @param string $name
     */
    public function setName(string $name = '')
    {
        $this->autoNaming = ($name === ''); //if no name we activate autoNaming
        $this->name = new Text($name);
    }

    protected function doNaming()
    {
        if ($this->name->isEmpty() || $this->autoNaming) {
            $newName = 'fk_';

            $hash = [];
            if ($this->getForeignEntity()) {
                $hash[] = $this->getForeignEntity()->getFullTableName()->toString();
            }
            $hash[] = implode(',', $this->localFields->toArray());
            $hash[] = implode(',', $this->foreignFields->toArray());

            $newName .= substr(md5(strtolower(implode(':', $hash))), 0, 6);

            if ($this->getEntity()) {
                $newName = $this->getEntity()->getTableName()->append("_$newName");
            }

            $this->name = $newName;
            $this->autoNaming = true;
        }
    }

    /**
     * Returns the refName for this foreign key (if any).
     *
     * @return string
     */
    public function getRefName(): string
    {
        return $this->refName;
    }

    /**
     * Sets a refName to use for this foreign key.
     *
     * @param string $name
     */
    public function setRefName(string $name)
    {
        $this->refName = $name;
    }

    /**
     * Returns the default join strategy for this foreign key (if any).
     *
     * @return string
     */
    public function getDefaultJoin(): string
    {
        return $this->defaultJoin;
    }

    /**
     * Sets the default join strategy for this foreign key (if any).
     *
     * @param string $join
     */
    public function setDefaultJoin(string $join)
    {
        $this->defaultJoin = $join;
    }

    /**
     * Returns the foreign table name of the FK, aka 'target'.
     *
     * @return string
     */
    public function getForeignEntityName(): ?string
    {
        if ('' === $this->foreignEntityName && isset($this->foreignEntity)) {
            $this->foreignEntityName = $this->foreignEntity->getFullName()->toString();
        }

        return $this->foreignEntityName;

//        $platform = $this->getPlatform();
//        if ($this->foreignSchemaName && $platform->supportsSchemas()) {
//            return $this->foreignSchemaName
//            . $platform->getSchemaDelimiter()
//            . $this->foreignEntityName;
//        }
//
//        $database = $this->getDatabase();
//        if ($database && ($schema = $this->parentEntity->guessSchemaName()) && $platform->supportsSchemas()) {
//            return $schema
//            . $platform->getSchemaDelimiter()
//            . $this->foreignEntityName;
//        }
//
//        return $this->foreignEntityName;
    }

    /**
     * @param string $foreignEntityName
     */
    public function setForeignEntityName(string $foreignEntityName): void
    {
        $this->foreignEntityName = $foreignEntityName;
    }

    /**
     * Returns the resolved foreign Entity model object.
     *
     * @return Entity|null
     */
    public function getForeignEntity(): ?Entity
    {
        if (isset($this->foreignEntity)) {
            return $this->foreignEntity;
        }

        if (($database = $this->getEntity()->getDatabase()) && $this->getForeignEntityName()) {
            return $database->getEntityByName($this->getForeignEntityName()) ??
                $database->getEntityByFullName($this->getForeignEntityName());
        }

        return null;
    }

    /**
     * @param null|Entity $foreignEntity
     */
    public function setForeignEntity(Entity $foreignEntity): void
    {
        $this->foreignEntity = $foreignEntity;
    }

    /**
     * Returns the name of the table the foreign key is in.
     *
     * @return Text
     */
    public function getEntityName(): Text
    {
        return $this->getEntity()->getName();
    }

    /**
     * Returns the name of the schema the foreign key is in.
     *
     * @return Text
     */
    public function getSchemaName(): Text
    {
        return $this->getEntity()->getSchemaName();
    }

    /**
     * Adds a new reference entry to the foreign key.
     *
     * @param Field|array|string $ref1 A Field object or an associative array or a string
     * @param Field|string $ref2 A Field object or a single string name
     */
    public function addReference($ref1, $ref2 = null)
    {
        if (is_array($ref1)) {
            $this->localFields->add($ref1['local'] ?? null);
            $this->foreignFields->add($ref1['foreign'] ?? null);

            return;
        }

        if (is_string($ref1)) {
            $this->localFields->add($ref1);
            $this->foreignFields->add(is_string($ref2) ? $ref2 : null);

            return;
        }

        $local = null;
        $foreign = null;
        if ($ref1 instanceof Field) {
            $local = $ref1->getName()->toString();
        }

        if ($ref2 instanceof Field) {
            $foreign = $ref2->getName()->toString();
        }

        $this->localFields->add($local);
        $this->foreignFields->add($foreign);
    }

    /**
     * Clears the references of this foreign key.
     *
     */
    public function clearReferences(): void
    {
        $this->localFields->clear();
        $this->foreignFields->clear();
    }

    /**
     * Returns an array of local field names.
     *
     * @return ArrayList
     */
    public function getLocalFields(): ArrayList
    {
        return $this->localFields;
    }

    /**
     * Returns an array of local field objects.
     *
     * @return ArrayList
     */
    public function getLocalFieldObjects(): ArrayList
    {
        return $this->getLocalFields()->map(function (string $fieldName): Field {
            $field = $this->getEntity()->getFieldByName($fieldName);
            if (null === $field) {
                throw new BuildException(sprintf(
                    'Field `%s` in local reference of relation `%s` from `%s` to `%s` not found.',
                    $fieldName,
                    $this->getName(),
                    $this->getEntity()->getName(),
                    $this->getForeignEntity()->getName()
                ));
            }

            return $field;
        });
    }

    /**
     * Returns a local Field object identified by a position.
     *
     * @param  integer $index
     *
     * @return Field
     */
    public function getLocalField(int $index = 0): Field
    {
        return $this->getEntity()->getFieldByName($this->getLocalFields()->get($index));
    }

    /**
     * Returns an array of local field to foreign field
     * mapping for this foreign key.
     *
     * @return array
     */
    public function getLocalForeignMapping(): array
    {
        $h = [];
        for ($i = 0, $size = $this->localFields->size(); $i < $size; $i++) {
            $h[$this->localFields->get($i)] = $this->foreignFields->get($i);
        }

        return $h;
    }

    /**
     * Returns an array of local field to foreign field
     * mapping for this foreign key.
     *
     * @return array
     */
    public function getForeignLocalMapping(): array
    {
        $h = [];
        for ($i = 0, $size = $this->localFields->size(); $i < $size; $i++) {
            $h[$this->foreignFields->get($i)] = $this->localFields->get($i);
        }

        return $h;
    }

    /**
     * Returns an array of local and foreign field objects
     * mapped for this foreign key.
     *
     * @return Field[][]
     */
    public function getFieldObjectsMapping(): array
    {
        $mapping = [];
        $foreignFields = $this->getForeignFieldObjects();
        for ($i = 0, $size = $this->localFields->size(); $i < $size; $i++) {
            $mapping[] = [
                'local' => $this->getEntity()->getFieldByName($this->localFields->get($i)),
                'foreign' => $foreignFields->get($i),
            ];
        }

        return $mapping;
    }

    /**
     * Returns an array of local and foreign field objects
     * mapped for this foreign key.
     *
     * Easy to iterate using
     *
     * foreach ($relation->getFieldObjectsMapArray() as $map) {
     *      list($local, $foreign) = $map;
     * }
     *
     * @return Field[]
     */
    public function getFieldObjectsMapArray(): array
    {
        $mapping = [];
        $foreignFields = $this->getForeignFieldObjects();
        for ($i = 0, $size = $this->localFields->size(); $i < $size; $i++) {
            $mapping[] = [$this->getEntity()->getFieldByName($this->localFields->get($i)), $foreignFields->get($i)];
        }

        return $mapping;
    }

    /**
     * Returns the foreign field name mapped to a specified local field.
     *
     * @param  string $local
     *
     * @return string
     */
    public function getMappedForeignField(string $local): ?string
    {
        $m = $this->getLocalForeignMapping();

        return $m[$local] ?? null;
    }

    /**
     * Returns the local field name mapped to a specified foreign field.
     *
     * @param  string $foreign
     *
     * @return string
     */
    public function getMappedLocalField(string $foreign): ?string
    {
        $mapping = $this->getForeignLocalMapping();

        return $mapping[$foreign] ?? null;
    }

    /**
     * Returns an array of foreign field names.
     *
     * @return ArrayList
     */
    public function getForeignFields(): ArrayList
    {
        return $this->foreignFields;
    }

    /**
     * Returns an array of foreign field objects.
     *
     * @return Field[]
     */
    public function getForeignFieldObjects(): ArrayList
    {
        $fields = new ArrayList();
        $foreignEntity = $this->getForeignEntity();
        foreach ($this->foreignFields as $fieldName) {
            $field = null;
            if (false !== strpos($fieldName, '.')) {
                [$relationName, $foreignFieldName] = explode('.', $fieldName);
                $foreignRelation = $this->getForeignEntity()->getRelation($relationName);
                if (!$foreignRelation) {
                    throw new BuildException(sprintf(
                            'Relation `%s` in Entity %s (%s) in foreign reference of relation `%s` from `%s` to `%s` not found.',
                            $relationName,
                            $this->getForeignEntity()->getName(),
                            $fieldName,
                            $this->getName(),
                            $this->getEntity()->getName(),
                            $this->getForeignEntity()->getName()
                        ));
                }
//                foreach ($foreignRelation->getFieldObjectsMapping() as $mapping) {
//                    /** @var Field $local */
//                    $local = $mapping['local'];
//                    /** @var Field $foreign */
//                    $foreign = $mapping['foreign'];
//                    if ($foreign->getName() === $foreignFieldName) {
//                        $field = clone $local;
//                        $field->foreignRelation = $foreignRelation;
//                        $field->foreignRelationFieldName = $foreignFieldName;
//                    }
//                }
            } else {
                $field = $foreignEntity->getFieldByName($fieldName);
            }

            if (null === $field) {
                throw new BuildException(sprintf(
                    'Field `%s` in foreign reference of relation `%s` from `%s` to `%s` not found.',
                    $fieldName,
                    $this->getName(),
                    $this->getEntity()->getName(),
                    $this->getForeignEntity()->getName()
                ));
            }
            $fields->add($field);
        }

        return $fields;
    }

    /**
     * Returns a foreign field object.
     *
     * @param integer $index
     *
     * @return Field
     */
    public function getForeignField(int $index = 0): Field
    {
        return $this->getForeignEntity()->getFieldByName($this->foreignFields->get($index));
    }

    /**
     * Returns whether this foreign key uses only required local fields.
     *
     * @return boolean
     */
    public function isLocalFieldsRequired(): bool
    {
        foreach ($this->localFields as $fieldName) {
            if (!$this->getEntity()->getFieldByName($fieldName)->isNotNull()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns whether this foreign key uses at least one required local field.
     *
     * @return boolean
     */
    public function isAtLeastOneLocalFieldRequired(): bool
    {
        foreach ($this->localFields as $fieldName) {
            if ($this->getEntity()->getFieldByName($fieldName)->isNotNull()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether this foreign key uses at least one required(notNull && no defaultValue) local primary key.
     *
     * @return boolean
     */
    public function isAtLeastOneLocalPrimaryKeyIsRequired(): bool
    {
        foreach ($this->getLocalPrimaryKeys() as $pk) {
            if ($pk->isNotNull() && !$pk->hasDefaultValue()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether this foreign key is also the primary key of the foreign
     * table.
     *
     * @return boolean Returns true if all fields inside this foreign key are primary keys of the foreign table
     */
    public function isForeignPrimaryKey(): bool
    {
        $lfmap = $this->getLocalForeignMapping();
        $foreignEntity = $this->getForeignEntity();

        $foreignPKCols = [];
        foreach ($foreignEntity->getPrimaryKey() as $fPKCol) {
            $foreignPKCols[] = $fPKCol->getName();
        }

        $foreignCols = [];
        foreach ($this->localFields as $colName) {
            $foreignCols[] = $foreignEntity->getFieldByName($lfmap[$colName])->getName();
        }

        return ((count($foreignPKCols) === count($foreignCols))
            && !array_diff($foreignPKCols, $foreignCols));
    }

    /**
     * Returns whether or not this foreign key relies on more than one
     * field binding.
     *
     * @return boolean
     */
    public function isComposite(): bool
    {
        return $this->localFields->size() > 1;
    }

    /**
     * Returns whether or not this foreign key is also the primary key of
     * the local table.
     *
     * @return boolean True if all local fields are at the same time a primary key
     */
    public function isLocalPrimaryKey(): bool
    {
        $localPKCols = [];
        foreach ($this->getEntity()->getPrimaryKey() as $lPKCol) {
            $localPKCols[] = $lPKCol->getName();
        }

        return count($localPKCols) === $this->localFields->size() && !array_diff($localPKCols, $this->localFields->toArray());
    }

    /**
     * Sets whether or not this foreign key should have its creation SQL
     * generated.
     *
     * @param boolean $skip
     */
    public function setSkipSql(bool $skip): void
    {
        $this->skipSql = $skip;
    }

    /**
     * Returns whether or not the SQL generation must be skipped for this
     * foreign key.
     *
     * @return boolean
     */
    public function isSkipSql(): bool
    {
        return $this->skipSql;
    }

    /**
     * Whether this foreign key is matched by an inverted foreign key (on foreign table).
     *
     * This is to prevent duplicate fields being generated for a 1:1 relationship that is represented
     * by foreign keys on both tables.  I don't know if that's good practice ... but hell, why not
     * support it.
     *
     * @return boolean
     * @link http://propel.phpdb.org/trac/ticket/549
     */
    public function isMatchedByInverseFK(): bool
    {
        return $this->getInverseFK() !== null;
    }

    public function getInverseFK(): ?Relation
    {
        $foreignEntity = $this->getForeignEntity();
        $map = $this->getForeignLocalMapping();

        foreach ($foreignEntity->getRelations() as $refFK) {
            $fkMap = $refFK->getLocalForeignMapping();
            // compares keys and values, but doesn't care about order, included check to make sure it's the same table (fixes #679)
            if (($refFK->getEntityName() === $this->getEntityName()) && ($map === $fkMap)) {
                return $refFK;
            }
        }

        return null;
    }

    /**
     * Returns the list of other foreign keys starting on the same table.
     * Used in many-to-many relationships.
     *
     * @return Relation[]
     */
    public function getOtherFks()
    {
        $fks = [];
        foreach ($this->getEntity()->getRelations() as $fk) {
            if ($fk !== $this) {
                $fks[] = $fk;
            }
        }

        return $fks;
    }

    /**
     * Whether at least one foreign field is also the primary key of the foreign table.
     *
     * @return boolean True if there is at least one field that is a primary key of the foreign table
     */
    public function isAtLeastOneForeignPrimaryKey(): bool
    {
        $cols = $this->getForeignPrimaryKeys();

        return 0 !== count($cols);
    }

    /**
     * Returns all foreign fields which are also a primary key of the foreign table.
     *
     * @return array Field[]
     */
    public function getForeignPrimaryKeys(): array
    {
        $lfmap = $this->getLocalForeignMapping();
        $foreignEntity = $this->getForeignEntity();

        $foreignPKCols = [];
        foreach ($foreignEntity->getPrimaryKey() as $fPKCol) {
            $foreignPKCols[$fPKCol->getName()->toString()] = true;
        }

        $foreignCols = [];
        foreach ($this->getLocalField() as $colName) {
            if ($foreignPKCols[$lfmap[$colName]]) {
                $foreignCols[] = $foreignEntity->getFieldByName($lfmap[$colName]);
            }
        }

        return $foreignCols;
    }

    /**
     * Returns all local fields which are also a primary key of the local table.
     *
     * @return ArrayList
     */
    public function getLocalPrimaryKeys(): ArrayList
    {
        return $this->getForeignFieldObjects()->filter(fn(Field $elem): bool => $elem->isPrimaryKey());
    }

    /**
     * Whether at least one local field is also a primary key.
     *
     * @return boolean True if there is at least one field that is a primary key
     */
    public function isAtLeastOneLocalPrimaryKey(): bool
    {
        return !$this->getLocalPrimaryKeys()->isEmpty();
    }

    public function setForeignSchema(string $foreignSchema): void
    {
        $this->foreignSchema = $foreignSchema;
    }

    public function getForeignSchema(): string
    {
        return $this->foreignSchema;
    }
}
