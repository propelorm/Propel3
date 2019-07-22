<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Command;

use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;
use Propel\Runtime\Adapter\AdapterFactory;
use Propel\Runtime\Connection\ConnectionFactory;
use Propel\Runtime\Connection\Exception\ConnectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/**
 * @author Marc Scholten <marcphilipscholten@gmail.com>
 */
class InitCommand extends AbstractCommand
{
    use SimpleTemplateTrait;

    private $defaultSchemaDir;
    private $defaultPhpDir;

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->defaultSchemaDir = getcwd();
        $this->defaultPhpDir = $this->detectDefaultPhpDir();
    }


    protected function configure()
    {
        parent::configure();

        $this
            ->setName('init')
            ->setDescription('Initializes a new project')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $options = [];
        $supportedRdbms = [
            'mysql' => 'MySQL',
            'sqlite' => 'SQLite',
            'pgsql' => 'PostgreSQL',
            'oracle' => 'Oracle',
            'sqlsrv' => 'MSSQL (via pdo-sqlsrv)',
            'mssql' => 'MSSQL (via pdo-mssql)'
        ];

        $io->title('Propel 3 Initializer');

        $io->section('First we need to set up your database connection.');
        $options['rdbms'] = $io->choice('Please pick your favorite database management system', $supportedRdbms, 'MySQL');

        do {
            switch ($options['rdbms']) {
                case 'mysql':
                    $options['dsn'] = $this->initMysql($io);
                    break;
                case 'sqlite':
                    $options['dsn'] = $this->initSqlite($io);
                    break;
                case 'pgsql':
                    $options['dsn'] = $this->initPgsql($io);
                    break;
                default:
                    $options['dsn'] = $this->initDsn($io, $options['rdbms']);
                    break;
            }

            $options['user'] = $io->ask('Please enter your database user', 'root');
            $options['password'] = $io->askHidden('Please enter your database password');
            $options['charset'] = $io->ask('Which charset would you like to use?', 'utf8');
        } while (!$this->testConnection($io, $options));

        $io->section('Propel schema');
        $io->text([
            'The initial step in every Propel project is the "build".',
            'During build time, a developer describes the structure of the datamodel in a XML file called the "schema".',
            'From this schema, Propel generates PHP classes, called "model classes", made of object-oriented PHP code',
            'optimized for a given RDMBS.',
            'The model classes are the primary interface to find and manipulate data in the database in Propel.',
            'The XML schema can also be used to generate SQL code to setup your database.',
            'Alternatively, you can generate the schema from an existing database.'
        ]);

        if ($io->confirm('Do you have an existing database you want to use with propel?', false)) {
            $options['schema'] = $this->reverseEngineerSchema($output, $options);
            $options['reverse'] = true;
        }

        $options['schemaDir'] = $io->ask('Where do you want to store your schema.xml?', $this->defaultSchemaDir);
        $options['phpDir'] = $io->ask('Where do you want propel to save the generated php models?', $this->defaultPhpDir);
        $options['namespace'] = $io->ask('Which namespace should the generated php models use?');

        $io->section('Propel configuration file');
        $io->text('Propel asks you to define some data to work properly, for instance: connection parameters, working directories, flags to take decisions and so on. You can pass these data via a configuration file.');
        $io->text('The name of the configuration file is <comment>propel</comment>, with one of the supported extensions (yml, xml, json, ini, php). E.g. <comment>propel.yml</comment> or <comment>propel.json</comment>.');

        $options['format'] = $io->ask('Please enter the format to use for the generated configuration file (yml, xml, json, ini, php)', 'yml', [$this, 'validateFormat']);

        $io->block('Propel 3 Initializer - Summary', null, 'bg=blue;fg=white');
        $io->text('The Propel 3 Initializer will set up your project with the following settings:');

        $io->listing($this->formatSummary([
            'Path to schema.xml' => $options['schemaDir'] . '/schema.xml',
            'Path to config file' => sprintf('%s/propel.%s', getcwd(), $options['format']),
            'Path to generated php models' => $options['phpDir'],
            'Namespace of generated php models' => $options['namespace'],
        ]));

        $io->listing($this->formatSummary([
            'Database management system' => $options['rdbms'],
            'Charset' => $options['charset'],
            'User' => $options['user'],
        ]));

        if (!$io->confirm('Is everything correct?')) {
            $io->error('Process aborted.');

            return 1;
        }

        $this->generateProject($io, $options);

        $io->text(sprintf('Propel 3 is ready to be used!'));
    }

    private function detectDefaultPhpDir()
    {
        if (file_exists(getcwd() . '/src/')) {
            $vendors = Finder::create()->directories()->in(getcwd() . '/src/')->depth(1);

            if ($vendors->count() > 1) {
                $iterator = $vendors->getIterator();
                $iterator->next();

                return $iterator->current() . '/Model/';
            }
        }

        return getcwd();
    }

    private function initMysql(StyleInterface $io)
    {
        $host = $io->ask('Please enter your database host', 'localhost');
        $port = $io->ask('Please enter your database port', '3306');
        $database = $io->ask('Please enter your database name');

        return sprintf('mysql:host=%s;port=%s;dbname=%s', $host, $port, $database);
    }

    private function initSqlite(StyleInterface $io)
    {
        $path = $io->ask('Where should the sqlite database be stored?', getcwd() . '/my.app.sq3');

        return sprintf('sqlite:%s', $path);
    }

    private function initPgsql(StyleInterface $io)
    {
        $host = $io->ask('Please enter your database host (without port)', 'localhost');
        $port = $io->ask('Please enter your database port', '5432');
        $database = $io->ask('Please enter your database name');

        return sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);
    }

    private function initDsn(StyleInterface $io, $rdbms)
    {
        switch ($rdbms) {
            case 'oracle':
                $help = 'https://php.net/manual/en/ref.pdo-oci.connection.php#refsect1-ref.pdo-oci.connection-description';
                break;
            case 'sqlsrv':
                $help = 'https://php.net/manual/en/ref.pdo-sqlsrv.connection.php#refsect1-ref.pdo-sqlsrv.connection-description';
                break;
            case 'mssql':
                $help = 'https://php.net/manual/en/ref.pdo-dblib.connection.php#refsect1-ref.pdo-dblib.connection-description';
                break;
            default:
                $help = 'https://php.net/manual/en/pdo.drivers.php';
        }

        return $io->ask(sprintf('Please enter the dsn (see <comment>%s</comment>) for your database connection', $help));
    }

    private function generateProject(StyleInterface $io, array $options)
    {
        $schema = __DIR__ . '/templates/schema.xml.mustache';
        $config = __DIR__ . '/templates/propel.' . $options['format'] . '.mustache';
        $distConfig = __DIR__ . '/templates/propel.' . $options['format'] . '.dist.mustache';

        if (!isset($options['schema'])) {
            $options['schema'] = $this->renderTemplate($options, $schema);
        }

        $this->writeFile($io, sprintf('%s/schema.xml', $options['schemaDir']), $options['schema']);
        $this->writeFile($io, sprintf('%s/propel.%s', getcwd(), $options['format']), $this->renderTemplate($options, $config));
        $this->writeFile($io, sprintf('%s/propel.%s.dist', getcwd(), $options['format']), $this->renderTemplate($options, $distConfig));

        $this->buildSqlAndModelsAndConvertConfig();
    }

    private function buildSqlAndModelsAndConvertConfig()
    {
        $this->getApplication()->setAutoExit(false);

        $followUpCommands = [
            'sql:build',
            'model:build',
            'config:convert',
        ];

        foreach($followUpCommands as $command) {
            if (0 !== $this->getApplication()->run(new StringInput($command))) {
                exit(1);
            }
        }

        $this->getApplication()->setAutoExit(true);
    }

    private function writeFile(StyleInterface $io, $filename, $content)
    {
        $this->getFilesystem()->dumpFile($filename, $content);

        $io->text(sprintf('<info> + %s</info>', $filename));
    }

    public function validateFormat($format)
    {
        $format = strtolower($format);

        if ($format === 'yaml') {
            $format = 'yml';
        }

        $validFormats = ['php', 'ini', 'yml', 'xml', 'json'];
        if (!in_array($format, $validFormats)) {
            throw new \InvalidArgumentException(sprintf(
                'The specified format "%s" is invalid. Use one of %s',
                $format,
                implode(', ', $validFormats)
            ));
        }

        return $format;
    }

    private function testConnection(SymfonyStyle $io, array $options)
    {
        $adapter = AdapterFactory::create($options['rdbms']);

        try {
            ConnectionFactory::create($options, $adapter);
            $io->block('Connected to sql server successful!', null, 'bg=blue;fg=white');

            return true;
        } catch (ConnectionException $e) {
            // get the "real" wrapped exception message
            do {
                $message = $e->getMessage();
            } while (($e = $e->getPrevious()) !== null);

            $io->error('Unable to connect to the specific sql server: ' . $message);
            $io->text('Make sure the specified credentials are correct and try it again.');

            if ($io->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
                $io->text($e);
            }

            return false;
        }
    }

    private function reverseEngineerSchema(OutputInterface $output, array $options)
    {
        $outputDir = sys_get_temp_dir();
        $this->getApplication()->setAutoExit(false);
        if (0 === $this->getApplication()->find('database:reverse')->run(
            new StringInput(sprintf(
            'reverse %s --output-dir %s --database-name %s',
            escapeshellarg($options['dsn'] . ';user=' . $options['user'] . ';password=' . $options['password']),
            $outputDir,
            'default'
        )),
                $output
        )) {
            $schema = file_get_contents($outputDir . '/schema.xml');
        } else {
            exit(1);
        }

        $this->getApplication()->setAutoExit(true);

        return $schema;
    }

    private function formatSummary($items)
    {
        $strings = [];
        foreach ($items as $name => $value) {
            $strings[] = sprintf('<info>%s</info>: <comment>%s</comment>', $name, $value);
        }

        return $strings;
    }
}
