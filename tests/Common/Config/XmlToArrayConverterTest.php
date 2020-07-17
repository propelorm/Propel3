<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config;

use Propel\Common\Config\Exception\InvalidArgumentException;
use Propel\Common\Config\Exception\XmlParseException;
use Propel\Common\Config\XmlToArrayConverter;
use Propel\Tests\TestCase;
use Propel\Tests\VfsTrait;

class XmlToArrayConverterTest extends TestCase
{
    use VfsTrait;

    public function provider()
    {
        return [
            [<<< XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <title>Star Wars</title>
 </movie>
 <movie>
  <title>The Lord Of The Rings</title>
 </movie>
</movies>
XML
, ['movie' => [0 => ['title' => 'Star Wars'], 1 => ['title' => 'The Lord Of The Rings']]]
            ],
            [<<< XML
<?xml version="1.0" encoding="utf-8"?>
<config>
  <log>
    <logger name="defaultLogger">
      <type>stream</type>
      <path>/var/log/propel.log</path>
      <level>300</level>
    </logger>
    <logger name="bookstore">
      <type>stream</type>
      <path>/var/log/propel_bookstore.log</path>
    </logger>
  </log>
</config>
XML
, ['log' => [
                'logger' => [
                    [
                        'type' => 'stream',
                        'path' => '/var/log/propel.log',
                        'level' => '300',
                        'name' => 'defaultLogger',
                    ],
                    [
                        'type' => 'stream',
                        'path' => '/var/log/propel_bookstore.log',
                        'name' => 'bookstore',
                    ],
                ],
            ]]
            ],
            [<<<XML
<?xml version="1.0" encoding="utf-8"?>
<config>
  <datasources default="bookstore">
      <adapter>mysql</adapter>
      <connection>
        <dsn>mysql:host=localhost;dbname=bookstore</dsn>
      </connection>
      <slaves>
       <connection>
        <dsn>mysql:host=slave-server1;dbname=bookstore</dsn>
       </connection>
       <connection>
        <dsn>mysql:host=slave-server2;dbname=bookstore</dsn>
       </connection>
      </slaves>
  </datasources>
</config>
XML
, ['datasources' => [
    'adapter' => 'mysql',
    'connection' => ['dsn' => 'mysql:host=localhost;dbname=bookstore'],
    'slaves' => [
        'connection' => [
            ['dsn' => 'mysql:host=slave-server1;dbname=bookstore'],
            ['dsn' => 'mysql:host=slave-server2;dbname=bookstore'],
        ],
    ],
    'default' => 'bookstore',
    ]]
            ],
            [<<<XML
<?xml version="1.0" encoding="utf-8"?>
<config>
  <datasources default="bookstore">
      <adapter>mysql</adapter>
      <connection>
        <dsn>mysql:host=localhost;dbname=bookstore</dsn>
      </connection>
</datasources>
</config>
XML
, ['datasources' => [
        'adapter' => 'mysql',
        'connection' => [
            'dsn' => 'mysql:host=localhost;dbname=bookstore',
        ],
        'default' => 'bookstore',
    ]
  ]
            ],
            [<<<XML
<?xml version="1.0" encoding="utf-8"?>
<config>
  <profiler class="\Runtime\Runtime\Util\Profiler">
    <slowTreshold>0.2</slowTreshold>
    <details>
      <time name="Time" precision="3" pad="8" />
      <mem name="Memory" precision="3" pad="8" />
    </details>
    <innerGlue>: </innerGlue>
    <outerGlue> | </outerGlue>
  </profiler>
 </config>
XML
, ['profiler' => [
                'class' => '\Runtime\Runtime\Util\Profiler',
                'slowTreshold' => 0.2,
                'details' => [
                    'time' => ['name' => 'Time', 'precision' => 3, 'pad' => '8'],
                    'mem' => ['name' => 'Memory', 'precision' => 3, 'pad' => '8'],
                ],
                'innerGlue' => ': ',
                'outerGlue' => ' | '
            ]]
            ]
        ];
    }

    /**
     * @dataProvider provider
     */
    public function testConvertFromString($xml, $expected)
    {
        $actual = XmlToArrayConverter::convert($xml);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider provider
     */
    public function testConvertFromFile($xml, $expected)
    {
        $file = $this->newFile('testconvert.xml', $xml);
        $actual = XmlToArrayConverter::convert($file->url());

        $this->assertEquals($expected, $actual);
    }

    public function testInvalidFileNameThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid xml content");
        XmlToArrayConverter::convert(1);
    }

    public function testInexistentFileThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid xml content");

        XmlToArrayConverter::convert('nonexistent.xml');
    }

    public function testInvalidXmlThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid xml content");

        $invalidXml = <<< XML
No xml
only plain text
---------
XML;
        XmlToArrayConverter::convert($invalidXml);
    }

    public function testErrorInXmlThrowsException()
    {
        $this->expectException(XmlParseException::class);
        $this->expectExceptionMessage("An error occurred while parsing XML configuration file:");

        $xmlWithError = <<< XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <titles>Star Wars</title>
 </movie>
 <movie>
  <title>The Lord Of The Rings</title>
 </movie>
</movies>
XML;
        XmlToArrayConverter::convert($xmlWithError);
    }

    public function testMultipleErrorsInXmlThrowsException()
    {
        $this->expectException(XmlParseException::class);
        $this->expectExceptionMessage("Some errors occurred while parsing XML configuration file:
 - Fatal Error 76: Opening and ending tag mismatch: titles line 4 and title
 - Fatal Error 76: Opening and ending tag mismatch: movies line 4 and moviess
");

        $xmlWithErrors = <<< XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <titles>Star Wars</title>
 </movie>
 <movie>
  <title>The Lord Of The Rings</title>
 </movie>
</moviess>
XML;
        XmlToArrayConverter::convert($xmlWithErrors);
    }

    public function testEmptyFileReturnsEmptyArray()
    {
        $file = $this->newFile('empty.xml', '');
        $actual = XmlToArrayConverter::convert($file->url());

        $this->assertEquals([], $actual);
    }
}
