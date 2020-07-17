<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Schema\Loader;

use InvalidArgumentException;
use phootwork\file\exception\FileException;
use phootwork\file\File;
use phootwork\json\Json;
use phootwork\json\JsonException;
use phootwork\lang\Text;
use Symfony\Component\Config\Loader\FileLoader;

/**
 * JsonSchemaLoader loads schema in json format.
 *
 * @author Cristiano Cinotti
 */
class JsonSchemaLoader extends FileLoader
{
    /**
     * Loads an Json file.
     *
     * @param mixed  $file The resource
     * @param string $type The resource type
     * @return array
     *
     * @throws InvalidArgumentException  if schema file not found
     * @throws JsonException   if invalid json file or error in decoding
     * @throws FileException if schema file is not readable or not found
     */
    public function load($file, string $type = null): array
    {
        $file = new File($this->locator->locate($file));

        return Json::decode((string) $file->read());
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, string $type = null): bool
    {
        return Text::create($resource)->endsWith('.json');
    }
}
