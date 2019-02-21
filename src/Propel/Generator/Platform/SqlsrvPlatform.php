<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Platform;

/**
 * MS SQL Server using pdo_sqlsrv implementation.
 *
 * @author Benjamin Runnels
 */
class SqlsrvPlatform extends MssqlPlatform
{
    /**
     * @see Platform#getMaxFieldNameLength()
     */
    public function getMaxFieldNameLength(): int
    {
        return 128;
    }
}
