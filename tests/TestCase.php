<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Reverse\SchemaParserInterface;
use Propel\Runtime\Configuration;
use Propel\Runtime\Connection\ConnectionInterface;

class TestCase extends BaseTestCase
{
    protected function getDriver(): string
    {
        return 'sqlite';
    }

    /**
     * Makes the sql compatible with the current database.
     * Means: replaces ` etc.
     *
     * @param  string $sql
     * @param  string $source
     * @param  string $target
     * @return mixed
     */
    protected function getSql(string $sql, string $source = 'mysql', string $target = null): string
    {
        if (!$target) {
            $target = $this->getDriver();
        }

        if ('sqlite' === $target && 'mysql' === $source) {
            return preg_replace('/`([^`]*)`/', '[$1]', $sql);
        }
        if ('pgsql' === $target && 'mysql' === $source) {
            return preg_replace('/`([^`]*)`/', '"$1"', $sql);
        }
        if ('mysql' !== $target && 'mysql' === $source) {
            return str_replace('`', '', $sql);
        }

        return $sql;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (Configuration::$globalConfiguration) {
            Configuration::$globalConfiguration->reset();
            Configuration::$globalConfiguration->getSession()->reset();
        }
    }

    /**
     * Returns true if the current driver in the connection ($this->con) is $db.
     *
     * @param  string $db
     * @return bool
     */
    protected function isDb(string $db = 'mysql'): bool
    {
        return $this->getDriver() == $db;
    }

    /**
     * @return bool
     */
    protected function runningOnPostgreSQL(): bool
    {
        return $this->isDb('pgsql');
    }

    /**
     * @return bool
     */
    protected function runningOnMySQL(): bool
    {
        return $this->isDb('mysql');
    }

    /**
     * @return bool
     */
    protected function runningOnSQLite(): bool
    {
        return $this->isDb('sqlite');
    }

    /**
     * @return bool
     */
    protected function runningOnOracle(): bool
    {
        return $this->isDb('oracle');
    }

    /**
     * @return bool
     */
    protected function runningOnMSSQL(): bool
    {
        return $this->isDb('mssql');
    }

    /**
     * @return PlatformInterface
     */
    protected function getPlatform(): PlatformInterface
    {
        $className = sprintf('\\Propel\\Generator\\Platform\\%sPlatform', ucfirst($this->getDriver()));

        return new $className;
    }

    /**
     * @param ConnectionInterface $con
     * @return SchemaParserInterface
     */
    protected function getParser(ConnectionInterface $con): SchemaParserInterface
    {
        $className = sprintf('\\Propel\\Generator\\Reverse\\%sSchemaParser', ucfirst($this->getDriver()));

        $obj =  new $className($con);

        return $obj;
    }
}
