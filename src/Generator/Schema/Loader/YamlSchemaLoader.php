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
use phootwork\file\Exception\FileException;
use phootwork\file\File;
use phootwork\lang\Text;
use Propel\Common\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * YamlSchemaLoader loads schema in yaml format.
 *
 * @author Cristiano Cinotti
 */
class YamlSchemaLoader extends FileLoader
{
    /**
     * Loads a Yaml file.
     *
     * @param mixed  $file The resource
     * @param string $type The resource type
     * @return array
     *
     * @throws InvalidArgumentException if schema file not found
     * @throws ParseException if something goes wrong in parsing file
     * @throws FileException if schema file is not readable
     */
    public function load($file, string $type = null): array
    {
        $file = new File($this->locator->locate($file));

        $content = Yaml::parse($file->read()->toString());

        if (!is_array($content)) {
            throw new ParseException("The content of the schema file `{$file->getPathname()}` is not valid yaml.");
        }

        return $content;
    }

    /**
     * Returns true if this class supports the given resource.
     * Both 'yml' and 'yaml' extensions are accepted.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, string $type = null): bool
    {
        $resource = new Text($resource);

        return $resource->endsWith('.yaml') || $resource->endsWith('.yml');
    }
}
