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
     * @throws \Propel\Generator\Schema\Exception\InputOutputException     if schema file is not readable
     * @throws \Propel\Generator\Schema\Exception\InvalidArgumentException if invalid xml file
     */
    public function load($file, $type = null): array
    {
        $path = $this->locator->locate($file);

        if (!is_readable($path)) {
            throw new InputOutputException("You don't have permissions to access schema file $file.");
        }

        $xmlContent = file_get_contents($path);

        $content = XmlToArrayConverter::convert($this->normalize($xmlContent, $path));

        if ([] === $content) {
            throw new InvalidArgumentException("The schema file '$path' has invalid content.");
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

        return $text->endsWith('.xml');
    }

    private function normalize(string $xmlContent, string $path): string
    {
        if ('' === $xmlContent || $xmlContent[0] !== '<') {
            throw new InvalidArgumentException("The schema file '$path' has invalid content.");
        }

        return '<propel-schema>' . substr($xmlContent, strpos($xmlContent, '<database')) . '</propel-schema>';

    }
}
