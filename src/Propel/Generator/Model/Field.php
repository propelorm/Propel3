<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Model;

use Propel\Common\Collection\Map;
use Propel\Common\Collection\Set;
use Propel\Common\Types\BuildableFieldTypeInterface;
use Propel\Common\Types\FieldTypeInterface;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Parts\DescriptionPart;
use Propel\Generator\Model\Parts\DomainPart;
use Propel\Generator\Model\Parts\EntityPart;
use Propel\Generator\Model\Parts\GeneratorPart;
use Propel\Generator\Model\Parts\NamePart;
use Propel\Generator\Model\Parts\NodePart;
use Propel\Generator\Model\Parts\PlatformAccessorPart;
use Propel\Generator\Model\Parts\VendorPart;
use Propel\Generator\Platform\PlatformInterface;

/**
 * A class for holding data about a column used in an application.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Jon S. Stevens <jon@latchkey.com> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author Byron Foster <byron_foster@yahoo.com> (Torque)
 * @author Bernd Goldschmidt <bgoldschmidt@rapidsoft.de>
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Field
{
    use NodePart, DomainPart, NamePart, GeneratorPart, EntityPart, PlatformAccessorPart, DescriptionPart, VendorPart;

    const CONSTANT_PREFIX    = 'FIELD_';

    //
    // Model properties
    // ---------------------------------

    /**
     * @var string The name of the mapped column
     */
    private $columnName;

    /** @var string */
    private $singularName;

    /** @var bool  */
    private $isNotNull;

    /**
     * Native PHP type (scalar or class name)
     * @var string "string", "boolean", "int", "double"
     */
    private $phpType;

    /** @var int */
    private $position;

    /** @var bool  */
    private $isPrimaryKey;

    /** @var bool  */
    private $isUnique;

    /** @var bool  */
    private $isAutoIncrement;

    /** @var bool  */
    private $skipCodeGeneration = false;

    /** @var bool  */
    private $isLazyLoad;

    /**
     * @var bool
     */
    private $isPrimaryString;

    // only one type is supported currently, which assumes the
    // column either contains the classnames or a key to
    // classnames specified in the schema.    Others may be
    // supported later.

    /** @var string 'single' or 'false' are accepted values */
    private $inheritanceType;

    /** @var bool  */
    private $isEnumeratedClasses;

    /**
     * @var Map
     */
    private $inheritanceList;

    /**
     * @var bool
     */
    private $implementationDetail = false;

    // maybe this can be retrieved from vendor specific information
    private $needsTransactionInPostgres;

    /**
     * @var Set
     */
    protected $valueSet;

    /**
     * @var Set
     */
    protected $referrers;

    /**
     * Creates a new column and set the name.
     *
     * @param string $name The column's name
     * @param string $type The column's type
     * @param int $size The column's size
     */
    public function __construct(string $name = null, string $type = PropelTypes::VARCHAR, int $size = null)
    {
        if (null !== $name) {
            $this->setName($name);
        }

        $this->domain = new Domain();
        $this->setType($type);

        if (null !== $size) {
            $this->setSize($size);
        }

        $this->isAutoIncrement            = false;
        $this->isEnumeratedClasses        = false;
        $this->isLazyLoad                 = false;
        $this->isNestedSetLeftKey         = false;
        $this->isNestedSetRightKey        = false;
        $this->isNodeKey                  = false;
        $this->isNotNull                  = false;
        $this->isPrimaryKey               = false;
        $this->isPrimaryString            = false;
        $this->isTreeScopeKey             = false;
        $this->isUnique                   = false;
        $this->needsTransactionInPostgres = false;
        $this->valueSet = new Set();
        $this->inheritanceList = new Set();
        $this->referrers =  new Set();
        $this->mutatorVisibility = Model::VISIBILITY_PUBLIC;
        $this->accessorVisibility = Model::VISIBILITY_PUBLIC;
    }

    /**
     * @inheritdoc
     * @return Entity
     */
    protected function getSuperordinate(): ?Entity
    {
        return $this->entity;
    }

    /**
     * Returns the fully qualified column name (table.column).
     *
     * @return string
     */
    public function getFullyQualifiedName(): string
    {
        return $this->getEntity()->getName() . '.' . strtoupper($this->getName());
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return NamingTool::toStudlyCase($this->getName());
    }

    /**
     * @return mixed
     */
    public function getColumnName(): string
    {
        if (null == $this->columnName) {
            return NamingTool::toSnakeCase($this->getName());
        }

        return $this->columnName;
    }

    /**
     * @param string $columnName
     */
    public function setColumnName(string $columnName)
    {
        $this->columnName = $columnName;
    }

    public function setPhpType(string $phptype)
    {
        $this->phpType = $phptype;
    }

    /**
     * Returns whether or not the column name is plural.
     *
     * @return boolean
     */
    public function isNamePlural(): bool
    {
        return $this->getSingularName() !== $this->name;
    }

    /**
     * Returns the column singular name.
     *
     * @return string
     */
    public function getSingularName(): string
    {
        if ($this->singularName) {
            return $this->singularName;
        }

        return rtrim($this->name, 's');
    }

    /**
     * @param string $singularName
     */
    public function setSingularName(string $singularName)
    {
        $this->singularName = $singularName;
    }

    /**
     * Returns the full column constant name (e.g. EntityMapName::FIELD_COLUMN_NAME).
     *
     * @return string A column constant name for insertion into PHP code
     */
    public function getFQConstantName(): string
    {
        $classname = $this->getEntity()->getName() . 'EntityMap';
        $const = $this->getConstantName();

        return $classname.'::'.$const;
    }

    /**
     * Returns the column constant name.
     *
     * @return string
     */
    public function getConstantName(): string
    {
        return self::CONSTANT_PREFIX . strtoupper(NamingTool::toSnakeCase($this->getName()));
    }

    /**
     * Returns the type to use in PHP sources.
     *
     * If no types has been specified, then use result of getPhpNative().
     *
     * @return string
     */
    public function getPhpType(): string
    {
        return $this->phpType ? $this->phpType : $this->getPhpNative();
    }

    /**
     * Returns the location of this column within the table (one-based).
     *
     * @return integer
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Returns the location of this column within the table (one-based).
     *
     * @param integer $position
     */
    public function setPosition(int $position)
    {
        $this->position = (int) $position;
    }

    /**
     * Adds a new inheritance definition to the inheritance list and sets the
     * parent column of the inheritance to the current column.
     *
     * @param  Inheritance $inheritance
     * @return Inheritance
     */
    public function addInheritance(Inheritance $inheritance): Inheritance
    {
        $inheritance->setField($this);
        $this->inheritanceList->add($inheritance);
        $this->isEnumeratedClasses = true;

        return $inheritance;
    }

    /**
     * Returns the inheritance type.
     *
     * @return string
     */
    public function getInheritanceType(): string
    {
        return $this->inheritanceType;
    }

    public function setInheritanceType(string $type): void
    {
        $this->inheritanceType = $type;
    }

    /**
     * Returns the inheritance list.
     *
     * @return Set
     */
    public function getInheritanceList(): Set
    {
        return $this->inheritanceList;
    }

    /**
     * Returns the inheritance definitions.
     *
     * @return Set
     */
    public function getChildren(): Set
    {
        return $this->inheritanceList;
    }

    /**
     * Returns whether or not this column is a normal property or specifies
     * the classes that are represented in the table containing this column.
     *
     * @return boolean
     */
    public function isInheritance(): bool
    {
        return $this->inheritanceType === 'single';
    }

    /**
     * Returns whether or not possible classes have been enumerated in the
     * schema file.
     *
     * @return boolean
     */
    public function isEnumeratedClasses(): bool
    {
        return $this->isEnumeratedClasses;
    }

    /**
     * Returns whether or not the column is not null.
     *
     * @return boolean
     */
    public function isNotNull(): bool
    {
        return $this->isNotNull;
    }

    /**
     * Sets whether or not the column is not null.
     *
     * @param boolean $flag
     */
    public function setNotNull(bool $flag = true)
    {
        $this->isNotNull = $flag;
    }

    /**
     * Returns NOT NULL string for this column.
     *
     * @return string.
     */
    public function getNotNullString(): string
    {
        return $this->getPlatform()->getNullString($this->isNotNull);
    }

    /**
     * Sets whether or not the column is used as the primary string.
     *
     * The primary string is the value used by default in the magic
     * __toString method of an active record object.
     *
     * @param boolean $isPrimaryString
     */
    public function setPrimaryString(bool $isPrimaryString)
    {
        $this->isPrimaryString = $isPrimaryString;
    }

    /**
     * Returns true if the column is the primary string (used for the magic
     * __toString() method).
     *
     * @return boolean
     */
    public function isPrimaryString(): bool
    {
        return $this->isPrimaryString;
    }

    /**
     * Sets whether or not the column is a primary key.
     *
     * @param boolean $flag
     */
    public function setPrimaryKey(bool $flag = true)
    {
        $this->isPrimaryKey = (Boolean) $flag;
    }

    /**
     * Returns whether or not the column is the primary key.
     *
     * @return boolean
     */
    public function isPrimaryKey(): bool
    {
        return $this->isPrimaryKey;
    }

    /**
     * Returns whether or not the column must have a unique index.
     *
     * @return boolean
     */
    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    /**
     * Returns true if the column requires a transaction in PostGreSQL.
     *
     * @return boolean
     */
    public function requiresTransactionInPostgres(): bool
    {
        return $this->needsTransactionInPostgres;
    }

    /**
     * Returns whether or not this column is a foreign key.
     *
     * @return boolean
     */
    public function isRelation(): bool
    {
        return count($this->getRelations()) > 0;
    }

    /**
     * Returns whether or not this column is part of more than one foreign key.
     *
     * @return boolean
     */
    public function hasMultipleFK(): bool
    {
        return count($this->getRelations()) > 1;
    }

    /**
     * Returns the foreign key objects for this column.
     *
     * Only if it is a foreign key or part of a foreign key.
     *
     * @return Relation[]
     */
    public function getRelations(): array
    {
        return $this->getEntity()->getFieldRelations($this->name);
    }

    /**
     * @return FieldTypeInterface|BuildableFieldTypeInterface
     */
    public function getFieldType(): FieldTypeInterface
    {
        return $this->getGeneratorConfig()->getFieldType($this->getType());
    }

    /**
     * This field is a implementation detail when it is used only to maintain a relationship
     * but is not visible at the object itself.
     *
     * @return boolean
     */
    public function isImplementationDetail(): bool
    {
        return $this->implementationDetail;
    }

    /**
     * @param boolean $implementationDetail
     */
    public function setImplementationDetail(bool $implementationDetail)
    {
        $this->implementationDetail = $implementationDetail;
    }

    /**
     * Adds the foreign key from another table that refers to this column.
     *
     * @param Relation $fk
     */
    public function addReferrer(Relation $fk)
    {
       $this->referrers->add($fk);
    }

    /**
     * Returns the list of references to this column.
     *
     * @return Set
     */
    public function getReferrers(): Set
    {
        return $this->referrers;
    }


    /**
     * Returns whether or not this column has referrers.
     *
     * @return boolean
     */
    public function hasReferrers(): bool
    {
        return !$this->getReferrers()->isEmpty();
    }

    /**
     * Returns whether or not this column has a specific referrer for a
     * specific foreign key object.
     *
     * @param  Relation $fk
     * @return boolean
     */
    public function hasReferrer(Relation $fk)
    {
        return $this->getReferrers()->contains($fk);
    }

    /**
     * Clears all referrers.
     *
     */
    public function clearReferrers()
    {
        $this->getReferrers()->clear();
    }

    /**
     * Clears all inheritance children.
     *
     */
    public function clearInheritanceList()
    {
        $this->getInheritanceList()->clear();
    }

    /**
     * Sets the domain up for specified mapping type.
     *
     * Calling this method will implicitly overwrite any previously set type,
     * size, scale (or other domain attributes).
     *
     * @param string $mappingType
     */
    public function setDomainForType(string $mappingType)
    {
        $this->getDomain()->copy($this->getPlatform()->getDomainForType($mappingType));
    }

    /**
     * Sets the mapping column type.
     *
     * @param string $mappingType
     * @see Domain::setType()
     */
    public function setType(string $mappingType)
    {
        $this->getDomain()->setType($mappingType);

        if (in_array($mappingType, [ PropelTypes::VARBINARY, PropelTypes::LONGVARBINARY, PropelTypes::BLOB ])) {
            $this->needsTransactionInPostgres = true;
        }
    }

    /**
     * Returns the Propel column type as a string.
     *
     * @return string
     * @see Domain::getType()
     */
    public function getType(): string
    {
        return $this->getDomain()->getType();
    }

    /**
     * Returns the column PDO type integer for this column's mapping type.
     *
     * @return integer
     * @deprecated use PropelTypes::getPDOType()
     */
    public function getPDOType(): int
    {
        return PropelTypes::getPDOType($this->getType());
    }

    /**
     * @param PlatformInterface|null $platform
     *
     * @return bool
     */
    public function isDefaultSqlType(PlatformInterface $platform = null): bool
    {
        if (null === $this->domain
            || null === $this->domain->getSqlType()
            || null === $platform) {
            return true;
        }

        $defaultSqlType = $platform->getDomainForType($this->getType())->getSqlType();

        return $defaultSqlType === $this->getDomain()->getSqlType();
    }

    /**
     * Returns whether or not this column is a blob/lob type.
     *
     * @return boolean
     */
    public function isLobType(): bool
    {
        return PropelTypes::isLobType($this->getType());
    }

    /**
     * Returns whether or not this column is a text type.
     *
     * @return boolean
     */
    public function isTextType(): bool
    {
        return PropelTypes::isTextType($this->getType());
    }

    /**
     * Returns whether or not this column is a numeric type.
     *
     * @return boolean
     */
    public function isNumericType(): bool
    {
        return PropelTypes::isNumericType($this->getType());
    }

    /**
     * Returns whether or not this column is a boolean type.
     *
     * @return boolean
     */
    public function isBooleanType(): bool
    {
        return PropelTypes::isBooleanType($this->getType());
    }

    /**
     * Returns whether or not this column is a temporal type.
     *
     * @return boolean
     */
    public function isTemporalType(): bool
    {
        return PropelTypes::isTemporalType($this->getType());
    }

    /**
     * Returns whether or not the column is an array column.
     *
     * @return boolean
     */
    public function isPhpArrayType(): bool
    {
        return PropelTypes::isPhpArrayType($this->getType());
    }

    /**
     * Returns whether or not this column is an ENUM column.
     *
     * @return boolean
     */
    public function isEnumType(): bool
    {
        return $this->getType() === PropelTypes::ENUM;
    }

    /**
     * @return bool
     */
    public function isFloatingPointNumber(): bool
    {
        return in_array($this->getType(), [PropelTypes::FLOAT, PropelTypes::DOUBLE, PropelTypes::REAL]);
    }

    /**
     * Sets the list of possible values for an ENUM column.
     *
     * @param Set
     */
    public function setValueSet($valueSet)
    {
        if (is_string($valueSet)) {
            $valueSet = explode(',', $valueSet);
            $valueSet = array_map('trim', $valueSet);
        }

        if (is_array($valueSet)) {
            $valueSet = new Set($valueSet);
        }

        $this->valueSet = $valueSet;
    }

    /**
     * Returns the list of possible values for an ENUM column.
     *
     * @return Set
     */
    public function getValueSet(): Set
    {
        return $this->valueSet;
    }

    /**
     * Returns the column size.
     *
     * @return integer
     */
    public function getSize(): ?int
    {
        return $this->domain->getSize();
    }

    /**
     * Sets the column size.
     *
     * @param integer $size
     */
    public function setSize(int $size)
    {
        $this->domain->setSize($size);
    }

    /**
     * Returns the column scale.
     *
     * @return integer
     */
    public function getScale(): int
    {
        return $this->domain->getScale();
    }

    /**
     * Sets the column scale.
     *
     * @param integer $scale
     */
    public function setScale(int $scale)
    {
        $this->domain->setScale($scale);
    }

    /**
     * Returns the size and precision in brackets for use in an SQL DLL.
     *
     * Example: (size[,scale]) <-> (10) or (10,2)
     *
     * return string
     */
    public function getSizeDefinition(): string
    {
        return $this->domain->getSizeDefinition();
    }

    /**
     * Returns true if this table has a default value (and which is not NULL).
     *
     * @return bool
     */
    public function hasDefaultValue(): bool
    {
        return null !== $this->getDefaultValue();
    }

    /**
     * Returns a string that will give this column a default value in PHP.
     *
     * @return string
     */
    public function getDefaultValueString(): string
    {
        $defaultValue = $this->getDefaultValue();

        if (null === $defaultValue) {
            return 'null';
        }

        if ($this->isNumericType()) {
            $out = (float) $defaultValue->getValue();
            return (string) $out;
        }

        if ($this->isTextType() || $this->getDefaultValue()->isExpression()) {
            return sprintf("'%s'", str_replace("'", "\\'", $defaultValue->getValue()));
        }

        if ($this->getType() === PropelTypes::BOOLEAN) {
            return PropelTypes::booleanValue($defaultValue->getValue()) ? 'true' : 'false';
        }

        return sprintf("'%s'", $defaultValue->getValue());
    }

    /**
     * Sets a string that will give this column a default value.
     *
     * @param  FieldDefaultValue|mixed $defaultValue The column's default value
     * @return Field
     */
    public function setDefaultValue($defaultValue)
    {
        if (!$defaultValue instanceof FieldDefaultValue) {
            $defaultValue = new FieldDefaultValue($defaultValue, FieldDefaultValue::TYPE_VALUE);
        }

        $this->domain->setDefaultValue($defaultValue);
    }

    /**
     * Returns the default value object for this column.
     *
     * @return FieldDefaultValue
     * @see Domain::getDefaultValue()
     */
    public function getDefaultValue(): ?FieldDefaultValue
    {
        return $this->domain->getDefaultValue();
    }

    /**
     * Returns the default value suitable for use in PHP.
     *
     * @return mixed
     * @see Domain::getPhpDefaultValue()
     */
    public function getPhpDefaultValue()
    {
        return $this->domain->getPhpDefaultValue();
    }

    /**
     * Returns whether or the column is an auto increment/sequence value for
     * the target database. We need to pass in the properties for the target
     * database!
     *
     * @return boolean
     */
    public function isAutoIncrement(): bool
    {
        return $this->isAutoIncrement;
    }

    /**
     * Return whether or not the column has to be lazy loaded.
     *
     * For example, if a runtime query on the table doesn't hydrate this column
     * but a getter does.
     *
     * @return boolean
     */
    public function isLazyLoad(): bool
    {
        return $this->isLazyLoad;
    }

    public function setLazyLoad(bool $lazyLoad = false)
    {
        $this->isLazyLoad = $lazyLoad;
    }

    /**
     * Returns the auto-increment string.
     *
     * @return string
     */
    public function getAutoIncrementString(): string
    {
        if ($this->isAutoIncrement() && Model::ID_METHOD_NATIVE === $this->getEntity()->getIdMethod()) {
            return $this->getPlatform()->getAutoIncrement();
        }

        if ($this->isAutoIncrement()) {
            throw new EngineException(sprintf(
                'You have specified autoIncrement for column "%s", but you have not specified idMethod="native" for table "%s".',
                $this->name,
                $this->getEntity()->getName()
            ));
        }

        return '';
    }

    /**
     * Sets whether or not this column is an auto incremented value.
     *
     * Use isAutoIncrement() to find out if it is set or not.
     *
     * @param boolean $flag
     */
    public function setAutoIncrement(bool $flag = true): void
    {
        $this->isAutoIncrement = (Boolean) $flag;
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
     * Returns a string representation of the native PHP type which corresponds
     * to the Propel type of this column. Used in the generation of Base
     * objects.
     *
     * @return string
     * @deprecated use PropelTypes::getPhpNative()
     */
    public function getPhpNative(): string
    {
        return PropelTypes::getPhpNative($this->getType());
    }

    /**
     * Returns whether or not the column PHP native type is primitive type (aka
     * a boolean, an integer, a long, a float, a double or a string).
     *
     * @return boolean
     * @see PropelTypes::isPhpPrimitiveType()
     * @deprecated use PropelTypes::isPhpPrimitiveType()
     */
    public function isPhpPrimitiveType(): bool
    {
        return PropelTypes::isPhpPrimitiveType($this->getPhpType());
    }

    /**
     * Returns whether or not the column PHP native type is a primitive numeric
     * type (aka an integer, a long, a float or a double).
     *
     * @return boolean
     * @see PropelTypes::isPhpPrimitiveNumericType()
     * @deprecated use PropelTypes::isPhpPrimitiveNumericType()
     */
    public function isPhpPrimitiveNumericType(): bool
    {
        return PropelTypes::isPhpPrimitiveNumericType($this->getPhpType());
    }

    /**
     * Returns whether or not the column PHP native type is an object.
     *
     * @return boolean
     * @see PropelTypes::isPhpObjectType()
     * @deprecated use PropelTypes::isPhpObjectType()
     */
    public function isPhpObjectType(): bool
    {
        return PropelTypes::isPhpObjectType($this->getPhpType());
    }

    /**
     * Clones the current object.
     *
     */
    public function __clone()
    {
        $this->referrers = clone $this->referrers;
        $this->valueSet = clone $this->valueSet;
        $this->inheritanceList = clone $this->inheritanceList;
        if ($this->vendor) {
            $this->vendor = clone $this->vendor;
        }
        if ($this->domain) {
            $this->domain = clone $this->domain;
        }
    }

// if still used, move it to pluralizer. Create a standalone library to pluralize/
// singularize names.
//
//    /**
//     * Generates the singular form of a PHP name.
//     *
//     * @param  string $phpname
//     * @return string
//     */
//    public static function generatePhpSingularName(string $phpname): string
//    {
//        return rtrim($phpname, 's');
//    }
}
