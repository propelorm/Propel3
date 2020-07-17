<?php  declare(strict_types=1);

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Timestampable;

use Propel\Generator\Builder\Om\Component\ComponentTrait;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Builder\Om\RepositoryBuilder;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Field;

/**
 * Gives a model class the ability to track creation and last modification dates
 * Uses two additional fields storing the creation and update date
 *
 * @author François Zaninotto
 */
class TimestampableBehavior extends Behavior
{
    use ComponentTrait;

    public function __construct()
    {
        parent::__construct();

        $this->parameters->setAll([
            'create_field' => 'createdAt',
            'update_field' => 'updatedAt',
            'disable_created_at' => false,
            'disable_updated_at' => false,
        ]);
    }

    public function withUpdatedAt(): bool
    {
        return !$this->getParameter('disable_updated_at');
    }

    public function withCreatedAt(): bool
    {
        return !$this->getParameter('disable_created_at');
    }

    /**
     * Add the create_field and update_fields to the current entity
     */
    public function modifyEntity(): void
    {
        $entity = $this->getEntity();

        if ($this->withCreatedAt() && !$entity->hasFieldByName($this->getParameter('create_field'))) {
            $createField = new Field();
            $createField->setName($this->getParameter('create_field'));
            $createField->setType('TIMESTAMP');
            $entity->addField($createField);
        }
        if ($this->withUpdatedAt() && !$entity->hasFieldByName($this->getParameter('update_field'))) {
            $updateField = new Field();
            $updateField->setName($this->getParameter('update_field'));
            $updateField->setType('TIMESTAMP');
            $entity->addField($updateField);
        }
    }

    public function preUpdate(RepositoryBuilder $repositoryBuilder): string
    {
        if ($this->withUpdatedAt()) {
            $field = $this->getEntity()->getFieldByName($this->getParameter('update_field'))->getName();

            return "
\$writer = \$this->getEntityMap()->getPropWriter();

foreach (\$event->getEntities() as \$entity) {
    if (!\$this->getEntityMap()->isFieldModified(\$entity, '$field')) {
        \$writer(\$entity, '$field', \\Propel\\Runtime\\Util\\PropelDateTime::createHighPrecision());
    }
}
            ";
        }
    }

    public function preInsert(RepositoryBuilder $repositoryBuilder): string
    {
        $script = "\$writer = \$this->getEntityMap()->getPropWriter();

foreach (\$event->getEntities() as \$entity) {
";


        if ($this->withCreatedAt()) {
            $createdAtField = $this->getEntity()->getFieldByName($this->getParameter('create_field'))->getName();
            $script .= "
    if (!\$this->getEntityMap()->isFieldModified(\$entity, '$createdAtField')) {
        \$writer(\$entity, '$createdAtField', \\Propel\\Runtime\\Util\\PropelDateTime::createHighPrecision());
    }";
        }

        if ($this->withUpdatedAt()) {
            $updatedAtField = $this->getEntity()->getFieldByName($this->getParameter('update_field'))->getName();
            $script .= "
    if (!\$this->getEntityMap()->isFieldModified(\$entity, '$updatedAtField')) {
        \$writer(\$entity, '$updatedAtField', \\Propel\\Runtime\\Util\\PropelDateTime::createHighPrecision());
    }";
        }

        $script .= "
}";

        return $script;
    }

    public function queryBuilderModification(QueryBuilder $builder): void
    {
        $this->applyComponent('Query\\FilterMethods', $builder);
    }
}
