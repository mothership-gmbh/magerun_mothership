<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Workflow;

use Mothership\StateMachine\WorkflowAbstract;
use Symfony\Component\Console\Helper\Table;

/**
 * You can always use this abstract workflow as a blueprint for your implementations.
 */
abstract class AbstractWorkflow extends WorkflowAbstract
{
    /**
     * Really simple data transfer 'object'. The current implementation of the state machine
     * is based on processing an externally available variable. Instead the dto should be an
     * object later on so that we can use stricter type hinting.
     *
     * @var mixed
     */
    protected $dto = [];

    /**
     * The main method, which processes the state machine.
     *
     * @param mixed $args    You might pass external logic
     * @param bool  $saveLog If enabled, then all processed states will be
     *                       stored and can be processed in the acceptance method
     *                       later for debugging purpose
     *
     * @throws \Exception
     *
     * @return void|mixed
     */
    public function run($args = [], $saveLog = false)
    {
        if (array_key_exists('output', $args)) {
            $this->output = $args['output'];
        }

        parent::run($args, $saveLog);
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
     * @link http://php.net/manual/de/pdostatement.fetchall.php
     *
     * @param string $sql  A valid SQL query string (with variables)
     * @param array  $args An array with configuration values
     * @parma int    $pdoFetchType Default is \PDO::FETCH_ASSOC
     *
     * @return array
     */
    public function fetchAll(string $sql, array $args = [], $pdoFetchType = \PDO::FETCH_ASSOC): array
    {
        $pdo  = \Mage::getSingleton('core/resource')->getConnection('core_read')->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);

        return $stmt->fetchAll($pdoFetchType);
    }

    /**
     * Wrapper for doing query to the MySQL database.
     *
     * @link http://php.net/manual/de/pdostatement.fetch.php
     *
     * @param string $sql  A valid SQL query string (with variables)
     * @param array  $args An array with configuration values
     * @parma int    $pdoFetchType Default is \PDO::FETCH_ASSOC
     *
     * @return array
     */
    public function fetch(string $sql, array $args = [], $pdoFetchType = \PDO::FETCH_ASSOC): array
    {
        $pdo  = \Mage::getSingleton('core/resource')->getConnection('core_read')->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);

        return $stmt->fetch($pdoFetchType);
    }

    /**
     * Wrapper for execute a command to the MySQL database.
     *
     * @param string $sql  A valid SQL query string (with variables)
     * @param array  $args An array with configuration values
     *
     * @return array the resultset
     */
    public function exec(string $sql, array $args = [])
    {
        $pdo  = \Mage::getSingleton('core/resource')->getConnection('core_read')->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);
        return [
            'stmt' => $stmt,
            'pdo'  => $pdo
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

            $tab             = "";
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
}
