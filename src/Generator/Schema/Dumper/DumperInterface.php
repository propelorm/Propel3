<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Schema\Dumper;

use Propel\Generator\Model\Database;
use Propel\Generator\Model\Schema;

interface DumperInterface
{
    /**
     * Dumps a Database model into a text formatted version.
     *
     * @param  Database $database The database model
     * @return string   The dumped formatted output (XML, YAML, CSV...)
     */
    public function dump(Database $database): string;

    /**
     * Dumps a single Schema model into an XML formatted version.
     *
     * @param  Schema  $schema The schema model
     * @return string  The dumped formatted output (XML, YAML, CSV...)
     */
    public function dumpSchema(Schema $schema): string;
}
