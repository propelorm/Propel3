<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Schema\Loader;

use phootwork\file\exception\FileException;
use phootwork\file\File;
use phootwork\lang\Text;
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
     * @throws \InvalidArgumentException if schema file not found
     * @throws FileException if schema file is not readable
     * @throws InvalidArgumentException if invalid xml file
     */
    public function load($file, string $type = null): array
    {
        $file = new File($this->locator->locate($file));
        $content = XmlToArrayConverter::convert($this->normalize($file));

        if ([] === $content) {
            throw new InvalidArgumentException("The schema file `{$file->getPathname()}` has invalid content.");
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
    public function supports($resource, string $type = null): bool
    {
        return Text::create($resource)->endsWith('.xml');
    }

    /**
     * Add a root tag, needed to `XmlToArrayConverter` works properly.
     *
     * @param File $file
     *
     * @return string
     * @throws FileException
     */
    private function normalize(File $file): string
    {
        $xmlContent = $file->read();
        if ($xmlContent->isEmpty() || !$xmlContent->startsWith('<')) {
            throw new InvalidArgumentException("The schema file `{$file->getPathname()}` has invalid content.");
        }

        return $xmlContent->substring($xmlContent->indexOf('<database'))
            ->prepend('<propel-schema>')
            ->append('</propel-schema>')
            ->toString();
    }
}
