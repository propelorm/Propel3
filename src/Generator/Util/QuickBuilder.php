<?php declare(strict_types=1);

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Util;

use Propel\Generator\Builder\Om\EntityMapBuilder;
use Propel\Generator\Schema\SchemaReader;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Model\Entity;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Platform\SqlitePlatform;
use Propel\Generator\Reverse\SchemaParserInterface;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Configuration;
use Propel\Runtime\Connection\ConnectionInterface;

class QuickBuilder
{
    /**
     * The Xml.
     *
     * @var string
     */
    protected $schema;

    /**
     * The Database Schema.
     *
     * @var string
     */
    protected $schemaName;

    /**
     * @var PlatformInterface
     */
    protected $platform;

    /**
     * @var GeneratorConfigInterface
     */
    protected $config;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var SchemaParserInterface
     */
    protected $parser;

    /**
     * @var array
     */
    protected $classTargets = [
        'activerecordtrait',
        'object',
        'entitymap',
        'proxy',
        'query',
        'repository',
        'repositorystub',
        'querystub'
    ];

    /**
     * Identifier quoting for reversed database.
     *
     * @var bool
     */
    protected $identifierQuoting = false;

    /**
     * Map from full entity class name to full entity map class name,
     * build in this quickBuilder. Can be used to register those tableMaps
     * to Propel\Runtime\Configuration, so queries/models are aware of it.
     *
     * @var string[]
     */
    protected $knownEntityClassNames = [];

    /**
     * @var Configuration
     */
    public static $configuration;

    /**
     * @param string $schema
     */
    public function setSchema(string $schema): void
    {
        $this->schema = $schema;
    }

    /**
     * @return string
     */
    public function getSchema(): string
    {
        return $this->schema;
    }

    /**
     * @param string $schemaName
     */
    public function setSchemaName(string $schemaName): void
    {
        $this->schemaName = $schemaName;
    }

    /**
     * @return string
     */
    public function getSchemaName(): string
    {
        return $this->schemaName;
    }

    /**
     * @param \Propel\Generator\Reverse\SchemaParserInterface $parser
     */
    public function setParser(SchemaParserInterface $parser): void
    {
        $this->parser = $parser;
    }

    /**
     * @return \Propel\Generator\Reverse\SchemaParserInterface
     */
    public function getParser(): SchemaParserInterface
    {
        return $this->parser;
    }

    /**
     * Setter for the platform property
     *
     * @param PlatformInterface $platform
     */
    public function setPlatform(PlatformInterface $platform): void
    {
        $this->platform = $platform;
    }

    /**
     * Getter for the platform property
     *
     * @return PlatformInterface
     */
    public function getPlatform(): PlatformInterface
    {
        if (null === $this->platform) {
            $this->platform = new SqlitePlatform();
        }

        $this->platform->setIdentifierQuoting($this->identifierQuoting);

        return $this->platform;
    }

    /**
     * Setter for the config property
     *
     * @param GeneratorConfigInterface $config
     */
    public function setConfig(GeneratorConfigInterface $config): void
    {
        $this->config = $config;
    }

    /**
     * Getter for the config property
     *
     * @return GeneratorConfigInterface
     */
    public function getConfig(): GeneratorConfigInterface
    {
        if (null === $this->config) {
            $this->config = new QuickGeneratorConfig();
        }

        return $this->config;
    }

    /**
     * @param string $schema
     * @param string|null $dsn
     * @param string|null $user
     * @param string|null $pass
     * @param AdapterInterface|null $adapter
     *
     * @return Configuration
     * @throws \Exception
     */
    public static function buildSchema(
        string $schema, ?string $dsn = null, ?string $user = null, ?string $pass = null, AdapterInterface $adapter = null
    ): Configuration
    {
        $builder = new self;
        $builder->setSchema($schema);

        return $builder->build($dsn, $user, $pass, $adapter);
    }

    /**
     * @param string $dsn
     * @param string $user
     * @param string $pass
     * @param AdapterInterface $adapter
     * @param array $classTargets
     *
     * @return Configuration
     * @throws \Exception
     */
    public function build(
        ?string $dsn = null,
        ?string $user = null,
        ?string $pass = null,
        AdapterInterface $adapter = null,
        array $classTargets = null
    ): Configuration
    {
        if (null === $dsn) {
            $dsn = 'sqlite::memory:';
            if (strtolower(getenv('SQLITE_DB')) !== 'memory') {
                $sqliteFile = 'latest_quickbuilder_sqlite.db';
                $reflection = new \ReflectionClass('\Propel\Tests\TestCase');
                $sqliteFile = realpath(dirname($reflection->getFileName()) . '/../../') . '/' . $sqliteFile;
                if (file_exists($sqliteFile)) {
                    unlink($sqliteFile);
                }
                $dsn = 'sqlite:' . $sqliteFile;
            }
        }
        if (null === $adapter) {
            $adapter = new SqliteAdapter();
        }
        if (null === $classTargets) {
            $classTargets = $this->classTargets;
        }

        static::$configuration = Configuration::getCurrentConfigurationOrCreate();
        static::$configuration->closeConnections();

        $connectionConfiguration = [
            'dsn' => $dsn,
            'user' => $user,
            'password' => $pass,
            'classname' => '\Propel\Runtime\Connection\DebugPDO'
        ];

        if (static::$configuration->hasConnectionManager($this->getDatabase()->getName())) {
            //overwriting a connection with a wrong incompatible adapter could go horrible wrong, so we forbid it.
            throw new \InvalidArgumentException('Could not build due to already existing connection-manager ' . $this->getDatabase()->getName());
        }

        if (static::$configuration->hasAdapter($this->getDatabase()->getName())) {
            throw new \InvalidArgumentException('Could not build due to already existing an adapter ' . $this->getDatabase()->getName());
        }

        static::$configuration->setAdapter($this->getDatabase()->getName(), $adapter);
        static::$configuration->buildConnectionManager($this->getDatabase()->getName(), $connectionConfiguration);

        $this->buildSQL(static::$configuration->getConnectionManager($this->getDatabase()->getName())->getWriteConnection());
        $this->buildClasses($classTargets, true);

        $this->registerEntities(static::$configuration);

        return static::$configuration;
    }

    public function setAdapter(AdapterInterface $adapter): void
    {
        static::$configuration->setAdapter($this->getDatabase()->getName(), $adapter);
    }

    public function getDatabase(): Database
    {
        if (null === $this->database) {
            $reader = new SchemaReader();
            $reader->setGeneratorConfig($this->getConfig());
            $appData = $reader->parseString($this->schema);
            $this->database = $appData->getDatabase();
        }

        $this->database->setPlatform($this->getPlatform());

        return $this->database;
    }

    /**
     * @param ConnectionInterface $con
     *
     * @return int
     * @throws \Exception
     */
    public function buildSQL(ConnectionInterface $con): int
    {
        $sql = $this->getSQL();
        $statements = SqlParser::parseString($sql);
        foreach ($statements as $statement) {
            if (strpos($statement, 'DROP') === 0) {
                // drop statements cause errors since the entity doesn't exist
//                continue;
            }
            try {
                static::$configuration->debug('buildSQL: ' . $statement);
                $stmt = $con->prepare($statement);
                if ($stmt instanceof \PDOStatement) {
                    // only execute if has no error
                    $stmt->execute();
                }
            } catch (\Exception $e) {
                echo implode("\n", $statements);
                throw new \Exception('SQL failed: ' . $statement, 0, $e);
            }
        }

        return count($statements);
    }

    /**
     * @param ConnectionInterface $con
     *
     * @return Database|null
     */
    public function updateDB(ConnectionInterface $con): ?Database
    {
        $database = $this->readConnectedDatabase();
        $diff = DatabaseComparator::computeDiff($database, $this->database);

        if (false === $diff) {
            return null;
        }
        $sql = $this->database->getPlatform()->getModifyDatabaseDDL($diff);

        $statements = SqlParser::parseString($sql);
        foreach ($statements as $statement) {
            try {
                $stmt = $con->prepare($statement);
                $stmt->execute();
            } catch (\Exception $e) {
                //echo $sql; //uncomment for better debugging
                throw new BuildException(
                    sprintf(
                        "Can not execute SQL: \n%s\nFrom database: \n%s\n\nTo database: \n%s\n\nDiff:\n%s",
                        $statement,
                        $this->database,
                        $database,
                        $diff
                    ),
                    null,
                    $e
                );
            }
        }

        return $database;
    }

    /**
     * @return Database
     */
    public function readConnectedDatabase(): Database
    {
        $this->getDatabase();
        $database = new Database();
        $database->setSchema($this->database->getSchema());
        $database->setName($this->database->getName());
        $database->setPlatform($this->getPlatform());
        $this->getParser()->parse($database);

        return $database;
    }

    public function getSQL()
    {
        return $this->getPlatform()->getAddEntitiesDDL($this->getDatabase());
    }

    public function getBuildName(string $classTargets = null): string
    {
        $entitys = [];
        foreach ($this->getDatabase()->getEntities() as $entity) {
            if (count($entitys) > 3) {
                break;
            }
            $entitys[] = $entity->getName();
        }
        $name = implode('_', $entitys);
        if (!$classTargets || count($classTargets) == 5) {
            $name .= '-all';
        } else {
            $name .= '-' . implode('_', $classTargets);
        }

        return $name;
    }

    /**
     * @param array $classTargets array('entitymap', 'object', 'query', 'activerecordtrait', 'querystub')
     * @param bool  $separate     pass true to get for each class a own file. better for debugging.
     */
    public function buildClasses(?array $classTargets = null, bool $separate = false)
    {
        $classes = $classTargets === null ? $this->classTargets : $classTargets;

        $dirHash = substr(sha1(getcwd()), 0, 10);
        $dir = sys_get_temp_dir() . "/propelQuickBuild-$dirHash/";

        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $includes = [];
        $allCode = '';
        $allCodeName = [];
        foreach ($this->getDatabase()->getEntities() as $entity) {
            if (5 > count($allCodeName)) {
                $allCodeName[] = $entity->getName();
            }

            if ($separate) {
                foreach ($classes as $class) {
                    $code = $this->getClassesForEntity($entity, [$class]);
                    $tempFile = $dir
                        . str_replace('\\', '-', $entity->getFullName())
                        . "-$class"
                        . '.php';
                    file_put_contents($tempFile, "<?php\n" . $code);
                    $includes[] = $tempFile;
                }

                if ($entity->hasAdditionalBuilders()) {
                    $code = $this->getClassesFromAdditionalBuilders($entity);
                    $tempFile = $dir
                        . str_replace('\\', '-', $entity->getFullName())
                        . 'additional.php';
                    file_put_contents($tempFile, "<?php\n" . $code);
                    $includes[] = $tempFile;
                }
            } else {
                $code = $this->getClassesForEntity($entity, $classes);
                if ($entity->hasAdditionalBuilders()) {
                    $code .= $this->getClassesFromAdditionalBuilders($entity);
                }
                $allCode .= $code;
            }
        }
        if ($separate) {
            foreach ($includes as $tempFile) {
                include($tempFile);
            }
        } else {
            $tempFile = $dir . join('_', $allCodeName) . '.php';
            file_put_contents($tempFile, "<?php\n" . $allCode);
            include($tempFile);
        }
    }

    public function getClasses(?array $classTargets = null): string
    {
        $script = '';
        foreach ($this->getDatabase()->getEntities() as $entity) {
            $script .= $this->getClassesForEntity($entity, $classTargets);
        }

        return $script;
    }

    /**
     * @param Configuration $configuration
     */
    public function registerEntities(Configuration $configuration = null): void
    {
        if (!$configuration) {
            $configuration = Configuration::getCurrentConfiguration();
        }

        foreach ($this->knownEntityClassNames as $databaseName => $entityNames) {
            $configuration->registerEntity($databaseName, $entityNames);
        }
    }

    public function getClassesForEntity(Entity $entity, ?array $classTargets = null): string
    {
        if (null === $classTargets) {
            $classTargets = $this->classTargets;
        }

        $script = '';

        foreach ($classTargets as $target) {
            $builder = $this->getConfig()->getConfiguredBuilder($entity, $target);
            if ($builder instanceof EntityMapBuilder) {
                $dbName = $builder->getEntity()->getDatabase()->getName();
                $fullEntityClassName = $builder->getEntity()->getFullName();
                $this->knownEntityClassNames[$dbName][] = $fullEntityClassName;
            }
            $source = $builder->build();
            $script .= $this->fixNamespaceDeclarations($source);
        }

        if ($col = $entity->getChildrenField()) {
            if ($col->isEnumeratedClasses()) {
                foreach ($col->getChildren() as $child) {
                    if ($child->getAncestor()) {
                        $builder = $this->getConfig()->getConfiguredBuilder($entity, 'queryinheritance');
                        $builder->setChild($child);
                        $class = $builder->build();
                        $script .= $this->fixNamespaceDeclarations($class);

                        foreach (['objectmultiextend', 'queryinheritancestub'] as $target) {
                            $builder = $this->getConfig()->getConfiguredBuilder($entity, $target);
                            $builder->setChild($child);
                            $class = $builder->build();
                            $script .= $this->fixNamespaceDeclarations($class);
                        }
                    }
                }
            }
        }

        $script = str_replace('<?php', '', $script);

        return $script;
    }

    public static function debugClassesForEntity(string $schema, string $entityName)
    {
        $builder = new self;
        $builder->setSchema($schema);
        foreach ($builder->getDatabase()->getEntities() as $entity) {
            if ($entity->getName() == $entityName) {
                echo $builder->getClassesForEntity($entity);
            }
        }
    }

    /**
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/ClassLoader/ClassCollectionLoader.php
     */
    public function fixNamespaceDeclarations(string $source): string
    {
        $source = $this->forceNamespace($source);

        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        $inNamespace = false;
        $tokens = token_get_all($source);

        for ($i = 0, $max = count($tokens); $i < $max; $i++) {
            $token = $tokens[$i];
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                // strip comments
                $output .= $token[1];
            } elseif (T_NAMESPACE === $token[0]) {
                if ($inNamespace) {
                    $output .= "}\n";
                }
                $output .= $token[1];

                // namespace name and whitespaces
                while (($t = $tokens[++$i]) && is_array($t) && in_array(
                        $t[0],
                        [T_WHITESPACE, T_NS_SEPARATOR, T_STRING]
                    )) {
                    $output .= $t[1];
                }
                if (is_string($t) && '{' === $t) {
                    $inNamespace = false;
                    --$i;
                } else {
                    $output .= "\n{";
                    $inNamespace = true;
                }
            } else {
                $output .= $token[1];
            }
        }

        if ($inNamespace) {
            $output .= "}\n";
        }

        return $output;
    }

    /**
     * Prevent generated class without namespace to fail.
     *
     * @param  string $code
     *
     * @return string
     */
    protected function forceNamespace(string $code): string
    {
        if (0 === preg_match('/\nnamespace/', $code)) {
            return "\nnamespace\n{\n" . $code . "\n}\n";
        }

        return $code;
    }

    /**
     * @return boolean
     */
    public function isIdentifierQuotingEnabled(): bool
    {
        return $this->identifierQuoting;
    }

    /**
     * @param boolean $identifierQuoting
     */
    public function setIdentifierQuoting(bool $identifierQuoting): void
    {
        $this->identifierQuoting = $identifierQuoting;
    }

    /**
     * @param $entity
     *
     * @return string
     */
    protected function getClassesFromAdditionalBuilders(Entity $entity): string
    {
        if ($entity->hasAdditionalBuilders()) {
            foreach ($entity->getAdditionalBuilders() as $builderClass) {
                $builder = new $builderClass($entity);
                $builder->setGeneratorConfig($this->getConfig());
                $code = $builder->build();
                $code = str_replace('<?php', '', $code);

                return $this->fixNamespaceDeclarations($code);
            }
        }
    }
}
