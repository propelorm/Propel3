<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config\Loader;

use InvalidArgumentException;
use phootwork\file\exception\FileException;
use phootwork\file\File;
use phootwork\lang\Text;
use Propel\Common\Config\Exception\InputOutputException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * YamlFileLoader loads configuration parameters from yaml file.
 *
 * @author Cristiano Cinotti
 */
class YamlFileLoader extends FileLoader
{
    /**
     * Loads a Yaml file.
     *
     * @param mixed $file The resource
     * @param string $type The resource type
     *
     * @return array
     *
     * @throws InvalidArgumentException if configuration file not found
     * @throws ParseException if something goes wrong in parsing file
     * @throws FileException if the file is not readable
     */
    public function load($file, string $type = null)
    {
        $file = new File($this->locator->locate($file));
        $content = Yaml::parse($file->read()->toString());

        //config file is empty
        $content = $content ?? [];

        //Invalid yaml content (e.g. text only) return a string
        if (!is_array($content)) {
            throw new ParseException('The content is not valid yaml.');
        }

        return $this->resolveParams($content);
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
    public function supports($resource, ?string $type = null): bool
    {
        $resource = new Text($resource);

        return $resource->endsWith('.yml') || $resource->endsWith('.yml.dist') || $resource->endsWith('.yaml') ||
            $resource->endsWith('.yaml.dist')
        ;
    }
}
