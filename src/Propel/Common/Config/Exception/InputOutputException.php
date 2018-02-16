<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config\Exception;

/**
 * Class InputOutputException
 *
 * This exception is thrown at runtime, if the configuration file doesn't exists or not readable.
 */
class InputOutputException extends RuntimeException implements ExceptionInterface
{
}
