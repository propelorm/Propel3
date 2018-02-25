<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Code;

use gossi\codegen\generator\CodeGenerator as GossiCodeGenerator;

/**
 * @author Gregor Panek <gp@gregorpanek.de>
 */
class CodeGenerator extends GossiCodeGenerator
{
    /**
     * Set custom ModelGenerator
     * @param Prope\Generator\Code\ModelGenerator $generator
     */
    public function setModelGenerator(ModelGenerator $generator): void
    {
        $this->generator = $generator;
    }
}
