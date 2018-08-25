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

use phootwork\lang\Text;
use Propel\Generator\Schema\Exception\InputOutputException;
use Propel\Generator\Schema\Exception\InvalidArgumentException;
use Propel\Generator\Schema\Exception\JsonParseException;
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
     * @throws \Propel\Generator\Schema\Exception\InvalidArgumentException   if invalid json file
     * @throws \Propel\Generator\Schema\Exception\InputOutputException       if schema file is not readable
     * @throws \Propel\Generator\Schema\Exception\JsonParseException         if error in parsing json schema
     */
    public function load($file, $type = null): array
    {
        $path = $this->locator->locate($file);

        if (!is_readable($path)) {
            throw new InputOutputException("You don't have permissions to access the schema file $file.");
        }

        $json = file_get_contents($path);

        if (false === $json) {
            throw new InvalidArgumentException('Error while reading the schema file');
        }

        $content = json_decode($json, true);
        $error = json_last_error();

        if (JSON_ERROR_NONE !== $error) {
            throw new JsonParseException($error);
        }

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
        if (!is_string($resource)) {
            return false;
        }
        $text = new Text($resource);

        return $text->endsWith('.json');
    }
}
