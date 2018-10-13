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
     * @throws \InvalidArgumentException                         if schema file not found
     * @throws \Symfony\Component\Yaml\Exception\ParseException  if something goes wrong in parsing file
     * @throws \phootwork\file\Exception\FileException           if schema file is not readable
     */
    public function load($file, $type = null): array
    {
        $file =new File($this->locator->locate($file));

        $content = Yaml::parse($file->read());

        if (!is_array($content)) {
            throw new ParseException('The content is not valid yaml.');
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
    public function supports($resource, $type = null): bool
    {
        $file = new File($resource);
        $extension = $file->getExtension();

        return ('yaml' === $extension) || ('yml' === $extension);
    }
}
