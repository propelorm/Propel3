<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config\Loader;

use phootwork\file\exception\FileException;
use phootwork\file\File;
use phootwork\json\Json;
use phootwork\json\JsonException;
use phootwork\lang\Text;

/**
 * JsonFileLoader loads configuration parameters from json file.
 *
 * @author Cristiano Cinotti
 */
class JsonFileLoader extends FileLoader
{
    /**
     * Loads an Json file.
     *
     * @param mixed $file The resource
     * @param string $type The resource type
     *
     * @return array
     *
     * @throws \InvalidArgumentException  if configuration file not found
     * @throws JsonException if something goes wrong while parsing json content
     * @throws FileException if configuration file is not readable
     */
    public function load($file, string $type = null): array
    {
        $file = new File($this->locator->locate($file));
        $json = $file->read();

        if ('' === $json) {
            return [];
        }

        return $json->isEmpty() ? [] : $this->resolveParams(Json::decode((string) $json));
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, ?string $type = null): bool
    {
        $resource = new Text($resource);

        return $resource->endsWith('.json') || $resource->endsWith('json.dist');
    }
}
