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
use Propel\Generator\Schema\Exception\InputOutputException;
use Propel\Generator\Schema\Exception\InvalidArgumentException;
use Symfony\Component\Config\Loader\FileLoader;

/**
 * PhpSchemaLoader loads schema in PHP format.
 *
 * The schema is expected to be in form of array. I.e.
 * <code>
 *     <?php
 *         return [
 *             'database' => [
 *             'name' => `my_db_name,
 *             .......................
 *         ];
 * </code>
 *
 * @author Cristiano Cinotti
 */
class PhpSchemaLoader extends FileLoader
{
    /**
     * Loads a PHP file.
     *
     * @param mixed  $file The resource
     * @param string $type The resource type
     * @return array
     *
     * @throws \InvalidArgumentException                                   if schema file not found
     * @throws \Propel\Generator\Schema\Exception\InvalidArgumentException if invalid file content
     * @throws \Propel\Generator\Schema\Exception\InputOutputException     if schema file is not readable
     */
    public function load($file, $type = null)
    {
        $path = $this->locator->locate($file);

        if (!is_readable($path)) {
            throw new InputOutputException("You don't have permissions to access schema file $file.");
        }

        //Use output buffering because in case $file contains invalid non-php content (i.e. plain text), include() function
        //write it on stdoutput
        ob_start();
        $content = include $path;
        ob_end_clean();

        if (!is_array($content)) {
            throw new InvalidArgumentException("The schema file '$file' has invalid content.");
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
        $extension = $file->getExtension();

        return ('php' === $extension) || ('inc') === $extension;
    }
}
