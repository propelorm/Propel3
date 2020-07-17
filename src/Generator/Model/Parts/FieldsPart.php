<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

use phootwork\collection\Set;
use phootwork\lang\Text;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Field;

/**
 * Trait Fields part.
 * Methods to manage a collection of fields.
 *
 * @author Cristiano Cinotti
 */
trait FieldsPart
{
    protected Set $fields;

    abstract protected function getEntity(): ?Entity;

    public function initFields(): void
    {
        $this->fields = new Set();
    }

    /**
     * Return the Field object with the given name.
     *
     * @param string $name
     *
     * @return Field|null
     */
    public function getFieldByName(string $name): ?Field
    {
        return $this->fields->find($name, fn(Field $element, string $query): bool => $element->getName()->toString() === $query);
    }

    /**
     * Return the Field object with the given name (case insensitive search).
     *
     * @param string $name
     *
     * @return null|Field
     */
    public function getFieldByLowercaseName(string $name): ?Field
    {
        return $this->fields->find($name,
            fn(Field $element, string $query): bool => $element->getName()->toLowerCase() === strtolower($query));
    }

    /**
     * Adds a new field to the object.
     * If the object is an Entity, the field name must be unique.
     *
     * @param Field $field
     *
     * @throws EngineException If the field is already added
     */
    public function addField(Field $field): void
    {
        if (null !== $this->getEntity()) {
            $field->setEntity($this->getEntity());
        }
        $this->fields->add($field);
    }

    /**
     * Adds several fields at once.
     *
     * @param Field[] $fields An array of Field instance
     */
    public function addFields(array $fields): void
    {
        if (null !== $this->getEntity()) {
            foreach ($fields as $field) {
                $field->setEntity($this->getEntity());
            }
        }
        $this->fields->add(...$fields);
    }

    /**
     * Returns whether or not the entity has a field.
     *
     * @param Field $field The Field object or its name
     *
     * @return bool
     */
    public function hasField(Field $field): bool
    {
        return $this->fields->contains($field);
    }

    /**
     * Returns whether or not the entity has a field.
     *
     * @param string $field The Field object or its name
     *
     * @return bool
     */
    public function hasFieldByName(string $field): bool
    {
        return $this->getFieldByName($field) !== null;
    }

    /**
     * Returns an array containing all Field objects.
     *
     * @return Set
     */
    public function getFields(): Set
    {
        return $this->fields;
    }

    /**
     * Removes a field from the fields collection.
     *
     * @param  Field $field The Field or its name
     *
     * @throws EngineException
     */
    public function removeField(Field $field): void
    {
        if (!$this->fields->contains($field)) {
            throw new EngineException(sprintf('No field named %s found in entity %s.', $field->getName(), $this->getName()));
        }

        $this->fields->remove($field);

        if ($this instanceof Entity) {
            $i = 1;
            foreach ($this->fields as $field) {
                $field->setPosition($i);
                $i++;
            }

            // @FIXME: also remove indexes on this field?
        }
    }

    public function removeFieldByName(string $name): void
    {
        $field = $this->getFieldByName($name);
        if (null === $field) {
            throw new EngineException(sprintf('No field named %s found in entity %s.', $name, $this->getName()));
        }

        $this->removeField($field);
    }
}
