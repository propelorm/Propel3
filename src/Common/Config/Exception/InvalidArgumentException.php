<?php declare(strict_types=1);

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config\Exception;

/**
 * Class InvalidArgumentException
 *
 * Specialized configuration exception, thrown when an invalid argument is passed to a method.
 */
class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}
