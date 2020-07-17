<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use phootwork\collection\Map;
use phootwork\lang\Text;
use Propel\Generator\Model\Parts\EntityPart;
use Propel\Generator\Model\Parts\FieldsPart;
use Propel\Generator\Model\Parts\NamePart;
use Propel\Generator\Model\Parts\SuperordinatePart;
use Propel\Generator\Model\Parts\VendorPart;

/**
 * Information about indices of a entity.
 *
 * @author Jason van Zyl <vanzyl@apache.org>
 * @author Daniel Rall <dlr@finemaltcoding.com>
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Index
{
    use EntityPart, FieldsPart, NamePart, SuperordinatePart, VendorPart;

    protected bool $autoNaming = false;

    /**
     * @var Map Map of `fieldname => size` to use for indexes creation.
     */
    protected Map $fieldSizes;

    /**
     * Creates a new Index instance.
     *
     * @param string $name Name of the index
     */
    public function __construct(string $name = '')
    {
        $this->initFields();
        $this->initVendor();
        $this->fieldSizes = new Map();
        $this->setName($name);
    }

    /**
     * @inheritdoc
     *
     * @return Entity
     */
    public function getSuperordinate(): Entity
    {
        return $this->getEntity();
    }

    /**
     * Returns the uniqueness of this index.
     *
     * @return boolean
     */
    public function isUnique(): bool
    {
        return false;
    }

    /**
     * Returns the index name.
     *
     * @return Text
     */
    public function getName(): Text
    {
        $this->doNaming();

        if (isset($this->entity) && $database = $this->entity->getDatabase()) {
            return $this->name->substring(0, $database->getPlatform()->getMaxFieldNameLength());
        }

        return $this->name;
    }

    protected function doNaming(): void
    {
        if ($this->name->isEmpty() || $this->autoNaming) {
            $newName = sprintf('%s_', $this instanceof Unique ? 'u' : 'i');

            if (!$this->fields->isEmpty()) {
                $hash[0] = '';
                $hash[1] = '';
                $this->fields->each(function (Field $element) use ($hash) {
                    $hash[0] .= $element->getName() . ', ';
                    $hash[1] .= $element->getSize() . ', ';
                });
                $hash = array_map(function ($element) {return substr($element, 0, -2);}, $hash);

                $newName .= substr(md5(strtolower(implode(':', $hash))), 0, 6);
            } else {
                $newName .= 'no_fields';
            }

            if ($this->getEntity()) {
                $newName = $this->getEntity()->getTableName()->append("_$newName");
            }

            $this->name = new Text($newName);
            $this->autoNaming = true;
        }
    }

    /**
     * Returns whether or not this index has a given field at a given position.
     *
     * @param  integer $pos             Position in the field list
     * @param  string  $name            Field name
     * @param  integer $size            Optional size check
     * @return boolean
     */
    public function hasFieldAtPosition(int $pos, string $name, int $size = null): bool
    {
        $fieldsArray = $this->getFields()->toArray();

        if (!isset($fieldsArray[$pos])) {
            return false;
        }

        /** @var Field $field */
        $field = $fieldsArray[$pos];

        if ($field->getName()->compare($name) !== 0) {
            return false;
        }

        if ($field->getSize() != $size) {
            return false;
        }

        return true;
    }

    public function getFieldSizes(): Map
    {
        return $this->fieldSizes;
    }
}
