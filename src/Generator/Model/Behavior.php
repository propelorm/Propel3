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
use phootwork\collection\Set;
use Propel\Generator\Builder\Om\ActiveRecordTraitBuilder;
use Propel\Generator\Builder\Om\EntityMapBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Builder\Om\RepositoryBuilder;
use Propel\Generator\Model\Parts\DatabasePart;
use Propel\Generator\Model\Parts\EntityPart;
use Propel\Generator\Model\Parts\NamePart;
use function DeepCopy\deep_copy;

/**
 * Information about behaviors of a entity.
 *
 * @author François Zaninotto
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 * @author Thomas Gossmann
 */
class Behavior
{
    use NamePart {
        setName as namePartSetName;
    }
    use EntityPart, DatabasePart;

    /**
     * The behavior id.
     *
     * @var string
     */
    protected string $id;

    /**
     * A collection of parameters.
     */
    protected Map $parameters;

    /**
     * Array of default parameters.
     * Usually override by subclasses.
     */
    protected array $defaultParameters = [];

    /**
     * Whether or not the entity has been
     * modified by the behavior.
     */
    protected bool $isEntityModified = false;

    /**
     * The absolute path to the directory
     * that contains the behavior's templates
     * files.
     */
    protected string $dirname;

    /**
     * A collection of additional builders.
     */
    protected array $additionalBuilders = [];

    /**
     * The order in which the behavior must
     * be applied.
     */
    protected int $entityModificationOrder = 50;

    public function __construct()
    {
        //Add the subclasses default parameters
        $this->parameters = new Map($this->defaultParameters);
    }

    /**
     * Sets the name of the Behavior
     *
     * @param string $name the name of the behavior
     */
    public function setName(string $name): void
    {
        if (!isset($this->id)) {
            $this->setId($name);
        }

        $this->namePartSetName($name);
    }

    /**
     * Sets the id of the Behavior
     *
     * @param string $id The id of the behavior
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Returns the id of the Behavior
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Indicates whether the behavior can be applied several times on the same
     * entity or not.
     *
     * @return bool
     */
    public function allowMultiple(): bool
    {
        return false;
    }

    /**
     * Sets a single parameter by its name.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setParameter(string $name, $value): void
    {
        //Don't want override a default parameter with a null value
        if (null !== $value) {
            $this->parameters->set(strtolower($name), $value);
        }
    }

    /**
     * Adds a single parameter.
     *
     * Expects an associative array:
     * ['name' => 'foo', 'value' => 'bar']
     *
     * @param array $parameter
     */
    public function addParameter(array $parameter): void
    {
        $this->parameters->set(strtolower($parameter['name']), $parameter['value']);
    }

    /**
     * Overrides the behavior parameters.
     *
     * Expects an associative array looking like [ 'foo' => 'bar' ].
     *
     * @param array $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters->clear();
        $this->parameters->setAll($parameters);
    }

    /**
     * Checks whether a parameter is set
     *
     * @param string $name
     * @return bool
     */
    public function hasParameter(string $name): bool
    {
        return $this->parameters->has($name);
    }

    /**
     * Returns a single parameter by its name.
     *
     * @param string   $name
     *
     * @return mixed
     */
    public function getParameter(string $name)
    {
        return $this->parameters->get($name);
    }

    /**
     * Returns the associative array of parameters.
     *
     * @return Map
     */
    public function getParameters(): Map
    {
        return $this->parameters;
    }

    /**
     * Defines when this behavior must execute its modifyEntity() method
     * relative to other behaviors. The bigger the value is, the later the
     * behavior is executed.
     *
     * Default is 50.
     *
     * @param integer $entityModificationOrder
     */
    public function setEntityModificationOrder(int $entityModificationOrder): void
    {
        $this->entityModificationOrder = $entityModificationOrder;
    }

    /**
     * Returns when this behavior must execute its modifyEntity() method relative
     * to other behaviors. The bigger the value is, the later the behavior is
     * executed.
     *
     * Default is 50.
     *
     * @return int
     */
    public function getEntityModificationOrder(): int
    {
        return $this->entityModificationOrder;
    }

    /**
     * This method is automatically called on database behaviors when the
     * database model is finished.
     *
     * Propagates the behavior to the entities of the database and override this
     * method to have a database behavior do something special.
     */
    public function modifyDatabase(): void
    {
        foreach ($this->getEntities() as $entity) {
            if ($entity->hasBehavior($this->getId())) {
                // don't add the same behavior twice
                continue;
            }
            $behavior = deep_copy($this);
            $entity->addBehavior($behavior);
        }
    }

    /**
     * Returns the list of all entities in the same database.
     *
     * @return Set A collection of Entity instance
     */
    protected function getEntities(): Set
    {
        return $this->database->getEntities();
    }

    /**
     * This method is automatically called on entity behaviors when the database
     * model is finished. It also override it to add columns to the current
     * entity.
     */
    public function modifyEntity()
    {
    }

    /**
     * Sets whether or not the entity has been modified.
     *
     * @param bool $modified
     */
    public function setEntityModified(bool $modified): void
    {
        $this->isEntityModified = $modified;
    }

    /**
     * Returns whether or not the entity has been modified.
     *
     * @return bool
     */
    public function isEntityModified(): bool
    {
        return $this->isEntityModified;
    }

    /**
     * Returns a column object using a name stored in the behavior parameters.
     * Useful for entity behaviors.
     *
     * @param string $name
     * @return Field
     */
    public function getFieldForParameter(string $name): Field
    {
        return $this->entity->getFieldByName($this->getParameter($name));
    }

    /**
     * Hook to change ObjectBuilder instance. Overwrite it and modify $builder if you want.
     *
     * @param ObjectBuilder $builder
     */
    public function objectBuilderModification(ObjectBuilder $builder)
    {
    }

    /**
     * Hook to change QueryBuilder instance. Overwrite it and modify $builder if you want.
     *
     * @param QueryBuilder $builder
     */
    public function queryBuilderModification(QueryBuilder $builder)
    {
    }

    /**
     * Hook to change RepositoryBuilder instance. Overwrite it and modify $builder if you want.
     *
     * @param RepositoryBuilder $builder
     */
    public function repositoryBuilderModification(RepositoryBuilder $builder)
    {
    }

    /**
     * Hook to change EntityMapBuilder instance. Overwrite it and modify $builder if you want.
     *
     * @param EntityMapBuilder $builder
     */
    public function entityMapBuilderModification(EntityMapBuilder $builder)
    {
    }

    /**
     * Hook to change ActiveRecordTraitBuilder instance. Overwrite it and modify $builder if you want.
     *
     * @param ActiveRecordTraitBuilder $builder
     */
    public function activeRecordTraitBuilderModification(ActiveRecordTraitBuilder $builder)
    {
    }

    /**
     * Returns the entity modifier object.
     *
     * The current object is returned by default.
     *
     * @return $this|Behavior
     */
    public function getEntityModifier()
    {
        return $this;
    }

    /**
     * Returns the object builder modifier object.
     *
     * The current object is returned by default.
     *
     * @return $this|Behavior
     */
    public function getObjectBuilderModifier()
    {
        return $this;
    }

    /**
     * Returns the query builder modifier object.
     *
     * The current object is returned by default.
     *
     * @return $this|Behavior
     */
    public function getQueryBuilderModifier()
    {
        return $this;
    }

    /**
     * Returns the entity map builder modifier object.
     *
     * The current object is returned by default.
     *
     * @return $this|Behavior
     */
    public function getEntityMapBuilderModifier()
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function getActiveRecordTraitBuilderModifier()
    {
        return $this;
    }

    /**
     * Returns whether or not this behavior has additional builders.
     *
     * @return bool
     */
    public function hasAdditionalBuilders(): bool
    {
        return !empty($this->additionalBuilders);
    }

    /**
     * Returns the list of additional builder objects.
     *
     * @return array
     */
    public function getAdditionalBuilders(): array
    {
        return $this->additionalBuilders;
    }

    public function preSave(RepositoryBuilder $builder)
    {
    }

    public function preInsert(RepositoryBuilder $builder)
    {
    }

    public function preUpdate(RepositoryBuilder $builder)
    {
    }

    public function postSave(RepositoryBuilder $builder)
    {
    }

    public function postInsert(RepositoryBuilder $builder)
    {
    }

    public function postUpdate(RepositoryBuilder $builder)
    {
    }
}
