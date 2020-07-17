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
use phootwork\lang\Text;
use Propel\Common\Config\Exception\InputOutputException;
use Propel\Common\Config\Exception\InvalidArgumentException;
use Propel\Common\Config\Exception\XmlParseException;
use Propel\Common\Config\XmlToArrayConverter;

/**
 * XmlFileLoader loads configuration parameters from xml file.
 *
 * @author Cristiano Cinotti
 */
class XmlFileLoader extends FileLoader
{
    /**
     * Loads an Xml file.
     *
     * @param mixed $file The resource
     * @param string $type The resource type
     *
     * @return array
     *
     * @throws \InvalidArgumentException if configuration file not found
     * @throws XmlParseException if something went wrong while parsing xml file
     * @throws FileException if the file is not readable
     */
    public function load($file, string $type = null): array
    {
        $file = new File($this->locator->locate($file));
        $content = XmlToArrayConverter::convert($file->read());

        return $this->resolveParams($content);
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

        return $resource->endsWith('.xml') || $resource->endsWith('.xml.dist');
    }
}
