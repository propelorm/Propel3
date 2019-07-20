<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

use Propel\Common\Collection\Set;
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

    public function initFields()
    {
        $this->fields =new Set([], Field::class);
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
        return $this->fields->find(function(Field $element) use ($name){
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
        return $this->fields->find(function(Field $element) use ($name){
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
     *
     * @return bool
     */
    public function hasField($field): bool
    {
        if ($field instanceof Field) {
            return $this->fields->contains($field);
        }

        return (bool) $this->getFieldByName($field);
    }

    /**
     * Returns the Field object with the specified name.
     *
     * @param string $name The name of the field (e.g. 'my_field')
     *
     * @return Field
     */
    public function getField(string $name): Field
    {
        if (!$this->hasField($name)) {
            $fieldsList = '';
            $this->fields->each(function (Field $element) use ($fieldsList) {
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
            $i = 1;
            foreach ($this->fields as $field) {
                $field->setPosition($i);
                $i++;
            }

            // @FIXME: also remove indexes on this field?
        }
    }
}
