<?php declare(strict_types=1);

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config\Loader;

use phootwork\json\Json;
use Propel\Common\Config\Exception\InputOutputException;
use Propel\Common\Config\Exception\InvalidArgumentException;

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
     * @param mixed  $file The resource
     * @param string $type The resource type
     * @return array
     *
     * @throws \InvalidArgumentException  if configuration file not found
     * @throws \Propel\Common\Config\Exception\InvalidArgumentException   if invalid json file
     * @throws \Propel\Common\Config\Exception\InputOutputException       if configuration file is not readable
     * @throws \phootwork\json\JsonException if something goes wrong while parsing json content
     */
    public function load($file, $type = null): array
    {
        $path = $this->locator->locate($file);

        if (!is_readable($path)) {
            throw new InputOutputException("You don't have permissions to access configuration file $file.");
        }

        $json = file_get_contents($path);

        if (false === $json) {
            throw new InvalidArgumentException('Error while reading configuration file');
        }

        if ('' === $json) {
            return [];
        }

        $content = $this->resolveParams(Json::decode($json)); //Resolve parameter placeholders (%name%)

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
        $info = pathinfo($resource);
        $extension = $info['extension'];

        if ('dist' === $extension) {
            $extension = pathinfo($info['filename'], PATHINFO_EXTENSION);
        }

        return is_string($resource) && ('json' === $extension);
    }
}
