<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Schema\Loader;

use phootwork\file\File;
use phootwork\json\Json;
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
     * @throws \InvalidArgumentException  if schema file not found
     * @throws \phootwork\json\JsonException   if invalid json file or error in decoding
     * @throws \phootwork\file\exception\FileException if schema file is not readable or not found
     */
    public function load($file, $type = null): array
    {
        $file = new File($this->locator->locate($file));
        $content = Json::decode($file->read());

        return $content;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null): bool
    {
        $file = new File($resource);

        return 'json' === $file->getExtension();
    }
}
