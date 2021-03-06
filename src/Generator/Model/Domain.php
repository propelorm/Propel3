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

use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Parts\DatabasePart;
use Propel\Generator\Model\Parts\NamePart;

/**
 * A class for holding data about a domain used in the schema.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Domain
{
    use DatabasePart, NamePart;

    /**
     * @var string
     */
    private $description;
    private $size;
    private $scale;
    private $mappingType;
    private $sqlType;

    /**
     * @var FieldDefaultValue
     */
    private $defaultValue;

    /**
     * If a property was manually replaced.
     *
     * @var bool
     */
    private $replaced = false;

    /**
     * Creates a new Domain object.
     *
     * If this domain needs a name, it must be specified manually.
     *
     * @param string  $type    Propel type.
     * @param string  $sqlType SQL type.
     * @param integer $size
     * @param integer $scale
     */
    public function __construct(string $type = null, string $sqlType = null, int $size = null, int $scale = null)
    {
        if (null !== $type) {
            $this->setType($type);
        }

        if (null !== $size) {
            $this->setSize($size);
        }

        if (null !== $scale) {
            $this->setScale($scale);
        }

        if (null !== $sqlType) {
            $this->setSqlType($sqlType);
        } elseif (null !== $type) {
            $this->setSqlType($type);
        }
    }

    /**
     * Copies the values from current object into passed-in Domain.
     *
     * @param Domain $domain Domain to copy values into.
     */
    public function copy(Domain $domain)
    {
        $this->defaultValue = $domain->getDefaultValue();
        $this->description = $domain->getDescription();
        $this->name = $domain->getName();
        $this->scale = $domain->getScale();
        $this->size = $domain->getSize();
        $this->sqlType = $domain->getSqlType();
        $this->mappingType = $domain->getType();
    }

    /**
     * Returns the domain description.
     *
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets the domain description.
     *
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * Returns the scale value.
     *
     * @return integer
     */
    public function getScale(): ?int
    {
        return $this->scale;
    }

    /**
     * Sets the scale value.
     *
     * @param integer $scale
     */
    public function setScale(int $scale)
    {
        $this->scale = $scale;
    }

    /**
     * Replaces the size.
     *
     * @param integer $scale
     */
    public function replaceScale(int $scale)
    {
        $this->setScale($scale);
    }

    /**
     * Returns the size.
     *
     * @return integer
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * Sets the size.
     *
     * @param integer $size
     */
    public function setSize(int $size)
    {
        $this->size = $size;
    }

    /**
     * Returns the mapping type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->mappingType;
    }

    /**
     * Sets the mapping type.
     *
     * @param string $mappingType
     */
    public function setType(string $mappingType)
    {
        $this->mappingType = $mappingType;
    }

    /**
     * Returns the default value object.
     *
     * @return FieldDefaultValue
     */
    public function getDefaultValue(): ?FieldDefaultValue
    {
        return $this->defaultValue;
    }

    /**
     * Returns the default value, type-casted for use in PHP OM.
     *
     * @return mixed
     */
    public function getPhpDefaultValue()
    {
        if (null === $this->defaultValue) {
            return null;
        }

        if ($this->defaultValue->isExpression()) {
            throw new EngineException('Cannot get PHP version of default value for default value EXPRESSION.');
        }

        if (in_array($this->mappingType, [ PropelTypes::BOOLEAN, PropelTypes::BOOLEAN_EMU ])) {
            return PropelTypes::booleanValue($this->defaultValue->getValue());
        }

        if (PropelTypes::PHP_ARRAY === $this->mappingType) {
            return $this->getDefaultValueForArray($this->defaultValue->getValue());
        }

        return $this->defaultValue->getValue();
    }

    /**
     * Sets the default value.
     *
     * @param FieldDefaultValue $value
     */
    public function setDefaultValue(FieldDefaultValue $value)
    {
        $this->defaultValue = $value;
    }

    /**
     * Returns the SQL type.
     *
     * @return string
     */
    public function getSqlType(): ?string
    {
        if (null === $this->sqlType) {
            return $this->getType();
        }

        return $this->sqlType;
    }

    /**
     * Sets the SQL type.
     *
     * @param string $sqlType
     */
    public function setSqlType(string $sqlType)
    {
        $this->sqlType = $sqlType;
    }

    /**
     * Replaces the SQL type if the new value is not null.
     *
     * @param string $sqlType
     */
    public function replaceSqlType(string $sqlType)
    {
        $this->setSqlType($sqlType);
        $this->replaced = true;
    }

    /**
     * Returns the size and scale in brackets for use in an sql schema.
     *
     * @return string
     */
    public function getSizeDefinition(): string
    {
        if (null === $this->size) {
            return '';
        }

        if (null !== $this->scale) {
            return sprintf('(%u,%u)', $this->size, $this->scale);
        }

        return sprintf('(%u)', $this->size);
    }

    public function isReplaced(): bool
    {
        return $this->replaced;
    }

    public function __clone()
    {
        if ($this->defaultValue) {
            $this->defaultValue = clone $this->defaultValue;
        }
    }

    protected function getDefaultValueForArray(?string $stringValue): ?string
    {
        $stringValue = trim($stringValue);

        if (empty($stringValue)) {
            return null;
        }

        $values = [];
        foreach (explode(',', $stringValue) as $v) {
            $values[] = trim($v);
        }

        $value = implode($values, ' | ');
        if (empty($value) || '|' === trim($value)) {
            return null;
        }

        return sprintf('||%s||', $value);
    }
}
