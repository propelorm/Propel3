<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Types;

use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Model\Field;

/**
 * Interface BuildableFieldTypeInterface
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
interface BuildableFieldTypeInterface
{
    /**
     * Allows you to modify the generated class of $builder. Use "$builder instanceof ObjectBuilder" to check which builder
     * you got.
     *
     * @param AbstractBuilder $builder
     * @param Field $field
     */
    public function build(AbstractBuilder $builder, Field $field): void;
}
