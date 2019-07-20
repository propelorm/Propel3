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
use Propel\Common\Config\XmlToArrayConverter;
use Propel\Generator\Schema\Exception\InvalidArgumentException;
use Symfony\Component\Config\Loader\FileLoader;

/**
 * XmlSchemaLoader loads the schema in xml format.
 *
 * @author Cristiano Cinotti
 */
class XmlSchemaLoader extends FileLoader
{
    /**
     * Loads an Xml file.
     *
     * @param mixed  $file The resource
     * @param string $type The resource type
     * @return array
     *
     * @throws \InvalidArgumentException                                   if schema file not found
     * @throws \phootwork\file\exception\FileException                     if schema file is not readable
     * @throws \Propel\Generator\Schema\Exception\InvalidArgumentException if invalid xml file
     */
    public function load($file, $type = null): array
    {
        $file = new File($this->locator->locate($file));
        $content = XmlToArrayConverter::convert($this->normalize($file));

        if ([] === $content) {
            throw new InvalidArgumentException("The schema file '{$file->getPathname()}' has invalid content.");
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
        $file = new File($resource);

        return 'xml' === $file->getExtension();
    }

    /**
     * Add a root tag, needed to `XmlToArrayConverter` works properly.
     *
     * @param File $file
     *
     * @return string
     * @throws \phootwork\file\exception\FileException
     */
    private function normalize(File $file): string
    {
        $xmlContent = $file->read();
        if ('' === $xmlContent || $xmlContent[0] !== '<') {
            throw new InvalidArgumentException("The schema file '{$file->getPathname()}' has invalid content.");
        }

        return '<propel-schema>' . substr($xmlContent, strpos($xmlContent, '<database')) . '</propel-schema>';
    }
}
