<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

declare(strict_types=1);

namespace Propel\Tests;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class VfsTestCase extends TestCase
{
    /**
     * @var vfsStreamDirectory;
     */
    private $root;

    public function getRoot(): vfsStreamDirectory
    {
        if (null === $this->root) {
            $this->root = vfsStream::setup();
        }

        return $this->root;
    }
}
