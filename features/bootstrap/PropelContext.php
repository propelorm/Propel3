<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Propel\Generator\Command\SqlBuildCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Base class for Propel acceptance tests
 *
 * @author William Durand
 * @author Cristiano Cinotti
 */
class PropelContext implements Context
{
    /**
     * @var string
     */
    protected $workingDirectory;

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters = null)
    {
        // Initialize your context here
        $this->workingDirectory = sys_get_temp_dir() . '/propel_behat';
    }

    /**
     * @BeforeScenario
     */
    public function cleanupWorkingDirectory()
    {
        $fs = new Filesystem();

        if ($fs->exists($this->workingDirectory)) {
            $fs->remove($this->workingDirectory);
        }

        $fs->mkdir($this->workingDirectory);
    }

    /**
     * Makes the sql compatible with the current database.
     * Means: replaces ` etc.
     *
     * @param  string $sql
     * @param  string $source
     * @param  string $target
     * @return mixed
     */
    protected function getSql($sql, $source = 'mysql', $target = null)
    {
        if (!$target) {
            $target = 'sqlite';
        }

        if ('sqlite' === $target && 'mysql' === $source) {
            return preg_replace('/`([^`]*)`/', '[$1]', $sql);
        }
        if ('pgsql' === $target && 'mysql' === $source) {
            return preg_replace('/`([^`]*)`/', '"$1"', $sql);
        }
        if ('mysql' !== $target && 'mysql' === $source) {
            return str_replace('`', '', $sql);
        }

        return $sql;
    }

    /**
     * Set up a standard test configuration
     *
     * @Given I have the standard configuration
     */
    public function iHaveTheStandardConfiguration()
    {
        $conf = "
propel:
    database:
        connections:
            default:
                adapter: sqlite
                dsn: sqlite::memory:
                user: root
                password:
    generator:
        defaultConnection: default
    runtime:
        defaultConnection: default
";
        file_put_contents($this->workingDirectory . '/propel.yml', $conf);
    }

    /**
     * Write a custom xml configuration into `propel.xml` file.
     *
     * @Given I have the configuration:
     */
    public function iHaveTheConfiguration(PyStringNode $string)
    {
        file_put_contents($this->workingDirectory . '/propel.xml', $string->getRaw());
    }

    /**
     * Write the xml schema into `schema.xml` file.
     *
     * @Given /^I have XML schema:$/
     */
    public function iHaveXmlSchema(PyStringNode $string)
    {
        file_put_contents($this->workingDirectory . '/schema.xml', $string->getRaw());
    }

    /**
     * Generate sql based on Propel schema, via running `sql:build` command.
     * Example: When  I generate SQL
     *
     * @When /^I generate SQL$/
     */
    public function iGenerateSql()
    {
        $app = new Application();
        $app->add(new SqlBuildCommand());
        $command = $app->find("sql:build");
        $input = new ArrayInput([
            'command' => 'sql:build',
            '--input-dir' => $this->workingDirectory,
            '--output-dir' => $this->workingDirectory,
            '-vv'
        ]);
        $output = new BufferedOutput();
        $command->run($input, $output);
    }
}
