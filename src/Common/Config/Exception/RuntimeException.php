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
 * Class RuntimeException
 *
 * Specialized configuration exception, for generic runtime errors.
 */
class RuntimeException extends \RuntimeException implements ExceptionInterface
{
}
