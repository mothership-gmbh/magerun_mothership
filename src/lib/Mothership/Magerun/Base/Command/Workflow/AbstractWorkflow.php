<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Workflow;

use Mothership\Exception\Exception;
use Mothership\StateMachine\WorkflowAbstract;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Yaml\Yaml;

/**
 * You can always use this abstract workflow as a blueprint for your implementations.
 */
abstract class AbstractWorkflow extends WorkflowAbstract
{
    /**
     * @var \DI\Container
     */
    protected $container;

    /**
     * Really simple data transfer 'object'. The current implementation of the state machine
     * is based on processing an externally available variable. Instead the dto should be an
     * object later on so that we can use stricter type hinting.
     *
     * @var mixed
     */
    protected $dto = [];
    /**
     * @var \DateTime|null
     */
    protected $date = null;

    /**
     * The log directory
     *
     * @var string
     */
    protected $logDir = null;
    /**
     * The name of the log file.
     *
     * @var string
     */
    protected $logFile = null;
    /**
     * The Magento root directory.
     *
     * @var string
     */
    protected $magentoRootDir = null;
    /**
     * Logs are enabled by default
     * You can disable explicitaly setting the option "--disable-logs" as from terminal line as from yaml workflow configuration
     * @var bool
     */
    protected $areLogsDisabled = false;

    protected $yaml = null;

    /**
     * The main method, which processes the state machine.
     *
     * @param mixed $args You might pass external logic
     *                                                 Important args are:
     *                                                 root-dir: magento root dir
     *                                                 log-dir: directory where to store log files
     *                                                 if this args is set log is automatically enabled
     *                                                 then all processed states will be
     *                                                 stored and can be processed in the acceptance method
     *                                                 later for debugging purpose
     * @param bool $logIntoMagentoScheduler If enabled the workflow process will be loged into Magento cron schedule table
     *
     * @throws \Exception
     *
     * @return void|mixed
     */
    public function run($args = [], $logIntoMagentoScheduler = true)
    {
        $this->date = new \DateTime();
        $saveLog = false;
        $this->output = array_key_exists('output', $args) ? $args['output'] : null;
        $this->input = array_key_exists('input', $args) ? $args['input'] : null;
        $this->magentoRootDir = array_key_exists('root-dir', $args) ? $args['root-dir'] : null;
        $this->yaml = array_key_exists('yaml', $args) ? $args['yaml'] : [];


        if ($this->input->getOption('disable-logs')) {
            $this->areLogsDisabled = true;
        } else if (array_key_exists('class', $this->yaml)) {
            if(array_key_exists('disable-logs', $this->yaml['class'])){
                $this->areLogsDisabled = true;
            }
        }

        /**
         * This is required for the initial arguments.
         * Do not remove this line. And if you want to refactor it,
         * build a test case with a subsequent workflow
         */
        $this->args = $args;

        /**
         * The log directory is mandatory argument. We will use the helper
         * to initially set the log path
         */
        if (array_key_exists('log-dir', $args)) {
            $this->logDir = $args['log-dir'];
            $this->initLogFile($args['log-dir']);
            $saveLog = true;
        }

        /**
         * Use the Table to display all arguments
         */
        $table = new Table($args['output']);
        $table->setHeaders(['key', 'value']);
        $argsTable = [];
        foreach ($args as $key => $arg) {
            if (!in_array($key,
                ['output', 'yaml', 'quiet', 'ansi', 'no-ansi', 'skip-config', 'skip-root-check', 'help', 'interactive', 'version', 'no-interaction'])
            ) {
                $argsTable[] = [$key, is_array($arg) ? 'Array' : $arg];
            }
        }
        $table->setRows($argsTable);
        $table->render();

        $this->loadContainer();

        $this->setInitialState();
        $lastInsertId = 0;
        if ($logIntoMagentoScheduler) {
            $sql
                = "INSERT INTO cron_schedule
                                (job_code,
                                 status,
                                 created_at,
                                 executed_at)
                    VALUES     (:job_code,
                                :status,
                                :created_at,
                                :executed_at)";
            $data = $this->exec($sql, [
                "job_code" => get_class($this),
                "status" => "running",
                "created_at" => date('Y-m-d H:m:i'),
                "executed_at" => date('Y-m-d H:m:i'),
            ]);
            $lastInsertId = $data['pdo']->lastInsertId();
        }

        parent::run($args, $saveLog);

        if ($logIntoMagentoScheduler && $lastInsertId > 0) {
            $sql = "UPDATE cron_schedule set status='success',finished_at=:finished_at WHERE schedule_id=:schedule_id";
            $this->exec($sql, [
                "schedule_id" => $lastInsertId,
                "finished_at" => date('Y-m-d H:m:i'),
            ]);
        }


    }

    /**
     * Instead of using the constructor put everything you need inside
     *
     * @return void
     */
    abstract public function initialize();

    /**
     * Wrapper for doing query to the MySQL database.
     *
     * @link  http://php.net/manual/de/pdostatement.fetchall.php
     *
     * @param string $sql A valid SQL query string (with variables)
     * @param array $args An array with configuration values
     *
     * @parma int    $pdoFetchType Default is \PDO::FETCH_ASSOC
     *
     * @return array
     */
    public function fetchAll($sql, array $args = [], $pdoFetchType = \PDO::FETCH_ASSOC)
    {
        $pdo = \Mage::getSingleton('core/resource')->getConnection('core_read')->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);

        $data = $stmt->fetchAll($pdoFetchType);
        $stmt->closeCursor();
        $stmt = null;
        $pdo = null;

        return $data;
    }


    /**
     * Wrapper for doing query to the MySQL database.
     *
     * @link  http://php.net/manual/de/pdostatement.fetch.php
     *
     * @param string $sql A valid SQL query string (with variables)
     * @param array $args An array with configuration values
     *
     * @parma int    $pdoFetchType Default is \PDO::FETCH_ASSOC
     *
     * @return array
     */
    public function fetch($sql, array $args = [], $pdoFetchType = \PDO::FETCH_ASSOC)
    {
        $pdo = \Mage::getSingleton('core/resource')->getConnection('core_read')->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);

        $data = $stmt->fetch($pdoFetchType);
        $stmt->closeCursor();
        $stmt = null;
        $pdo = null;

        if (false === empty($data)) {
            return $data;
        }

        return [];
    }

    /**
     * Wrapper for execute a command to the MySQL database.
     *
     * @param string $sql A valid SQL query string (with variables)
     * @param array $args An array with configuration values
     *
     * @return array the resultset
     */
    public function exec($sql, array $args = [])
    {
        $pdo = \Mage::getSingleton('core/resource')->getConnection('core_read')->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);

        return [
            'stmt' => $stmt,
            'pdo' => $pdo,
        ];
    }

    /**
     * Method to start tracking the execution time. Pass the flag -vvv
     *
     * @return void
     */
    public function preDispatch()
    {
        if (null !== $this->output && $this->output->isVeryVerbose()) {
            $this->microtime = microtime(true);

            $this->output->writeln('');
            $this->output->writeln('<info>' . "" . get_class($this) .
                ' <comment>' . $this->getCurrentStatus()->getName() . '</comment> <fg=white;bg=black>-></> </info>'
            );
        }
    }

    /**
     * Method to start tracking the execution time. Pass the flag -vvv
     *
     * @return void
     */
    public function postDispatch()
    {
        if (null !== $this->output && $this->output->isVeryVerbose()) {

            $tab = "";
            $time_elapsed_us = number_format(microtime(true) - $this->microtime, 10);

            $level = 'comment';
            if ($time_elapsed_us > 0.5) {
                $level = 'error';
            }

            $this->output->writeln('');
            $this->output->writeln('<info>' . $tab . get_class($this) .
                ' <comment>' . $this->getCurrentStatus()->getName() . '</comment> <fg=white;bg=black>//</> completed in <' . $level . '>' .
                (string)$time_elapsed_us . '</' . $level . '> seconds' .
                ' and consumed <comment>' . $this->convert(memory_get_usage(true)) . '</comment>' .
                '</info>'
            );
        }
    }

    /**
     * Function that convert int value from "memory_get_usage" function in a readable unit
     *
     * @param int $size
     *
     * @return string
     */
    protected function convert($size)
    {
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    /**
     * Increased verbosity of messages
     *
     * @param string $message
     *
     * @return void
     */
    public function v($message)
    {
        if ($this->output->isVerbose()) {
            $this->output->writeln("<fg=white;bg=black>" . $message . "</>");
        }
    }

    /**
     * Increased verbosity of messages for tables
     *
     * @param array $table
     *
     * @return void
     */
    public function rt(array $header = [], array $data)
    {
        if ($this->output->isVerbose()) {

            $table = new Table($this->output);
            $table
                ->setHeaders($header)
                ->setRows($data);
            $table->render();
        }
    }

    /**
     * Informative non essential messages
     *
     * @param string $message
     * @param bool $onLogFile if enabled the message will be print also into log file
     *
     * @return void
     */
    public function vv($message)
    {
        if ($this->output->isVeryVerbose()) {
            $this->output->writeln("<fg=white;bg=black>" . $message . "</>");
        }
    }

    /**
     * Messages on debug level
     *
     * @param string $message
     *
     * @return void
     */
    public function d($message)
    {
        if ($this->output->isDebug()) {
            $this->output->writeln("<fg=white;bg=black>" . $message . "</>");
        }
    }

    /**
     * Set the logfile base on a path. Also create the log directory if it does not exist
     *
     * @param string $logFile
     *
     * @return void
     */
    protected function initLogFile($path)
    {
        if (false === file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $date = new \DateTime();
        $path = $path . DIRECTORY_SEPARATOR . $date->format('Ymd') . '.log';

        //check if i'm using a relative or absolute path
        if (substr($path, 0, 1) != '/') {
            $this->logFile = getcwd() . '/' . $path;
        } else {
            $this->logFile = $path;
        }
    }

    /**
     * Add a new log
     *
     * @param array $message Pass the message as an array
     *
     * @return void
     */
    protected function log(array $message)
    {
        if (!$this->areLogsDisabled) {
            $date = $this->date->format('d-m-Y H:i:s');

            if (is_null($this->logFile) && isset($this->args['logFile'])) {
                $this->logFile = $this->args['logFile'];
            }
            if (is_null($this->logFile)) {
                return;
            }

            $message = array_merge([$date], $message);
            error_log(implode("\t", $message) . "\n", 3, $this->logFile);
        }
    }

    /**
     * Disable the Indexer.
     */
    protected function disableAutomaticIndexer()
    {
        $processes = [];
        $indexer = \Mage::getSingleton('index/indexer');
        foreach ($indexer->getProcessesCollection() as $process) {
            //store current process mode
            $processes[$process->getIndexerCode()] = $process->getMode();
            $this->output->writeln(
                '<comment>Set index [' . $process->getIndexerCode() . '] -> ' . \Mage_Index_Model_Process::MODE_MANUAL
                . '</comment>'
            );

            if ($process->getMode() != \Mage_Index_Model_Process::MODE_MANUAL) {
                $process->setMode(\Mage_Index_Model_Process::MODE_MANUAL)->save();
            }
        }
    }

    /**
     * Enable the Indexer.
     */
    protected function enableAutomaticIndexer()
    {
        $processes = [];
        $indexer = \Mage::getSingleton('index/indexer');
        foreach ($indexer->getProcessesCollection() as $process) {
            //store current process mode
            $processes[$process->getIndexerCode()] = $process->getMode();
            //set it to manual, if not manual yet.
            $this->output->writeln(
                '<comment>Set index [' . $process->getIndexerCode() . '] -> '
                . \Mage_Index_Model_Process::MODE_REAL_TIME . '</comment>'
            );

            if ($process->getMode() != \Mage_Index_Model_Process::MODE_REAL_TIME) {
                $process->setMode(\Mage_Index_Model_Process::MODE_REAL_TIME)->save();
            }
        }
    }

    /**
     * You can pass required dependent workflows in the configuration file.
     * Example:
     *
     * workflows:
     *   WorkflowImageImport:  app/etc/mothership/workflows/ProductsImageImport.yaml
     *
     * This will load the image import workflow into the di container
     *
     * @param array $definitions
     *
     * @throws \DI\NotFoundException
     */
    public function loadContainer(array $definitions = [])
    {
        if (null !== $this->container) return;

        /**
         * The database connection is used in pretty every workflow. Therefore it is defined as default
         *
         * @return mixed
         */
        $definitions['\PDO'] = function () {
            return \Mage::getSingleton('core/resource')->getConnection('core_read')->getConnection();
        };

        /**
         * The workflow configuration will be loaded dynamically if this is set in the yaml configuration file.
         */
        if (array_key_exists('workflows', $this->vars['class'])) {
            foreach ($this->vars['class']['workflows'] as $identifier => $configuration) {
                $configurationFile = sprintf('%s%s%s', $this->magentoRootDir, '/', $configuration);
                if (file_exists($configurationFile)) {
                    $parsedConfigurations = Yaml::parse(file_get_contents($configurationFile));
                    $definitions[$identifier] = function () use ($parsedConfigurations) {
                        return new \Mothership\StateMachine\StateMachine($parsedConfigurations);
                    };
                } else {
                    throw new Exception('Configuration file for identifier ' . $identifier . ' not found. Expected: ' . $configurationFile);
                }
            }
        }

        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions(
            $definitions
        );

        $this->container = $builder->build();
    }
}
