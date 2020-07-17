<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use phootwork\lang\Text;
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

    private string $description = '';
    private int $size;
    private int $scale;
    private string $mappingType = '';
    private string $sqlType = '';

    private ?FieldDefaultValue $defaultValue = null;

    /**
     * If a property was manually replaced.
     *
     * @var bool
     */
    private bool $replaced = false;

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
        $this->sqlType = $domain->getSqlType();
        $this->mappingType = $domain->getType();
        if (null !== $scale = $domain->getScale()) {
            $this->scale = $scale;
        }
        if (null !== $size = $domain->getSize()) {
            $this->size = $size;
        }
    }

    /**
     * Returns the domain description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Sets the domain description.
     *
     * @param string $description
     */
    public function setDescription(string $description): void
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
        return $this->scale ?? null;
    }

    /**
     * Sets the scale value.
     *
     * @param integer $scale
     */
    public function setScale(int $scale): void
    {
        $this->scale = $scale;
    }

    /**
     * Replaces the size.
     *
     * @param integer $scale
     */
    public function replaceScale(int $scale): void
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
        return $this->size ?? null;
    }

    /**
     * Sets the size.
     *
     * @param integer $size
     */
    public function setSize(int $size): void
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
    public function setType(string $mappingType): void
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
        return $this->defaultValue ?? null;
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
    public function setDefaultValue(FieldDefaultValue $value): void
    {
        $this->defaultValue = $value;
    }

    /**
     * Returns the SQL type.
     *
     * @return string
     */
    public function getSqlType(): string
    {
        if ('' === $this->sqlType) {
            return $this->getType();
        }

        return $this->sqlType;
    }

    /**
     * Sets the SQL type.
     *
     * @param string $sqlType
     */
    public function setSqlType(string $sqlType): void
    {
        $this->sqlType = $sqlType;
    }

    /**
     * Replaces the SQL type if the new value is not null.
     *
     * @param string $sqlType
     */
    public function replaceSqlType(string $sqlType): void
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
        if (!isset($this->size)) {
            return '';
        }

        if (isset($this->scale)) {
            return sprintf('(%u,%u)', $this->size, $this->scale);
        }

        return sprintf('(%u)', $this->size);
    }

    public function isReplaced(): bool
    {
        return $this->replaced;
    }

    protected function getDefaultValueForArray(string $stringValue): ?Text
    {
        $stringValue = trim($stringValue);
        $stringValue = $stringValue === '|' ? '' : $stringValue;
        $textValues = Text::create($stringValue)->split(',')->map('trim')->join(' | ');

        return $textValues->isEmpty() ? null : $textValues->ensureStart('||')->ensureEnd('||');
    }
}
