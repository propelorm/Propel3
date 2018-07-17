<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Model\Parts;

use phootwork\collection\Set;
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
    /**
     * @var Set
     */
    protected $fields;

    /**
     * Return the Field object with the given name.
     *
     * @param string $name
     *
     * @return Field|null
     */
    public function getFieldByName(string $name): ?Field
    {
        return $this->fields->find(function($element) use ($name){
            return $element->getName() === $name;
        });
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
        return $this->fields->find(function($element) use ($name){
            return strtolower($element->getName()) === strtolower($name);
        });
    }

    /**
     * Adds a new field to the object.
     * If the object is an Entity, the field name must be unique.
     *
     * @param Field $field
     *
     * @throws EngineException If the field is already added
     */
    public function addField(Field $field)
    {
        if ($this instanceof Entity) {
            //The field must be unique
            if (null !== $this->getFieldByName($field->getName())) {
                throw new EngineException(sprintf('Field "%s" declared twice in entity "%s"', $field->getName(), $this->getName()));
            }

            $field->setEntity($this);
        }

        $field->setEntity($this->getEntity());
        $this->fields->add($field);
    }

    /**
     * Adds several fields at once.
     *
     * @param Field[] $fields An array of Field instance
     */
    public function addFields(array $fields)
    {
        foreach ($fields as $field) {
            $this->addField($field);
        }
    }

    /**
     * Returns whether or not the entity has a field.
     *
     * @param Field|string $field The Field object or its name
     * @param bool $caseInsensitive Whether the check is case insensitive.
     *
     * @return bool
     */
    public function hasField($field, bool $caseInsensitive = false): bool
    {
        if ($field instanceof Field) {
            return $this->fields->contains($field);
        }

        if ($caseInsensitive) {
            return (bool) $this->getFieldByLowercaseName($field);
        }

        return (bool) $this->getFieldByName($field);
    }

    /**
     * Returns the Field object with the specified name.
     *
     * @param string $name The name of the field (e.g. 'my_field')
     * @param bool $caseInsensitive Whether the check is case insensitive.
     *
     * @return Field
     */
    public function getField(string $name, bool $caseInsensitive = false): Field
    {
        if (!$this->hasField($name, $caseInsensitive)) {
            $fieldsList = '';
            $this->fields->each(function ($element) use ($fieldsList) {
                $fieldsList .= $element->getName() . ', ';
            });
            $fieldsList = substr($fieldsList, 0, -2);

            throw new \InvalidArgumentException(sprintf(
                "Field `%s` not found in %s `%s` [%s]",
                $name,
                get_class($this),
                $this->getName(),
                $fieldsList)
            );
        }

        if ($caseInsensitive) {
            return $this->getFieldByLowercaseName($name);
        }

        return $this->getFieldByName($name);
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
     * @param  Field|string $field The Field or its name
     *
     * @throws EngineException
     */
    public function removeField($field)
    {
        if (is_string($field)) {
            $field = $this->getField($field);
        }

        if (null === $field || !$this->fields->contains($field)) {
            throw new EngineException(sprintf('No field named %s found in entity %s.', $field->getName(), $this->getName()));
        }

        $this->fields->remove($field);

        if ($this instanceof Entity) {
            $nbFields = $this->fields->size();
            for ($i = 0; $i < $nbFields; $i++) {
                $this->fields[$i]->setPosition($i + 1);
            }
            // @FIXME: also remove indexes on this field?
        }
    }
}
