<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Environment;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use \Mothership\Magerun\Base\Command\AbstractMagentoCommand;

use Mothership_Addons\Lib\Database;
use Mothership_Addons\Lib\File;
use Mothership_Addons\Lib\Logger;

/**
 * Class AbstractMagentoCommand.
 *
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 *
 * @link      http://www.mothership.de/
 */
class DumpCommand extends AbstractMagentoCommand
{
    protected $description = 'Dump all settings from the core_config_data and save them in the file settings.php';

    /**
     * The excluded paths are all paths in the table core_config_data
     * which are ignored by the dump. You should set them in the /resource/config.php
     *
     * @var array
     */
    protected $_excluded_paths;


    protected $_included_paths;

    /**
     * The configuration which should be exported
     *
     * @var mixed
     */
    protected $_config_to_be_exported;

    /**
     * @var string
     */
    protected $_base_path;

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_base_path = $this->getApplication()->getMagentoRootFolder() . '/app/etc/mothership/environments';

        $logger = Logger::getInstance();

        $this->detectMagento($output);
        if ($this->initMagento()) {


            $this->writeSection($output, 'Import the settings from the settings.php file');

            $this->_initConfig($output);

            $mode_settings = array(
                0 => 'Dump based on excluded paths',
                1 => 'Dump based on included paths',
            );
            $dialog = $this->getHelper('dialog');
            $mode = $dialog->select(
                $output,
                'Please select your mode',
                $mode_settings,
                0
            );
            $output->writeln('You have just selected: ' . $mode_settings[$mode]);

            $pdo = $this->_getDatabaseConnection();

            $query = "SELECT * FROM core_config_data";
            $sth = $pdo->prepare($query);
            $sth->execute();
            $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);


            if ($mode == 0) {
                $this->useExcludedPaths($rows);
            }

            if ($mode == 1) {
                $this->useIncludedPaths($rows);
            }

            // dump the log
            $logger->getData($this, $output);
            File::writePHPArray($this->_base_path . '/resource/dump.php', $this->_config_to_be_exported);
            $output->writeln('<comment>Output written to: ' . $this->_base_path . '/resource/dump.php</comment>');

        };
    }

    protected function useExcludedPaths($rows)
    {

        foreach ($rows as $row) {
            /**
             * Check, if a single row will be excluded
             */
            $exclude = false;
            foreach ($this->_excluded_paths as $_excluded_path_pattern) {
                if (preg_match($_excluded_path_pattern, $row['path'])) {
                    Logger::logCounter($_excluded_path_pattern);
                    $exclude = true;
                }
            }

            if (false === $exclude) {
                $this->_config_to_be_exported[$row['path']][] = array(
                    'scope_id' => $row['scope_id'],
                    'scope'    => $row['scope'],
                    'value'    => $row['value'],
                );
                Logger::logCounter('total');
            }
        }
    }

    protected function useIncludedPaths($rows)
    {

        foreach ($rows as $row) {
            /**
             * Check, if a single row will be excluded
             */
            $include = false;
            foreach ($this->_included_paths as $_included_path_pattern) {
                if (preg_match($_included_path_pattern, $row['path'])) {
                    Logger::logCounter($_included_path_pattern);
                    $include = true;
                }
            }

            if (true === $include) {
                $this->_config_to_be_exported[$row['path']][] = array(
                    'scope_id' => $row['scope_id'],
                    'scope'    => $row['scope'],
                    'value'    => $row['value'],
                );
                Logger::logCounter('total');
            }
        }
    }

    /**
     * Load the configuration and set the general values
     */
    protected function _initConfig($output)
    {
        $config = File::loadConfig($this->_base_path . '/resource/config.php');

        if (array_key_exists('excluded_paths', $config['dump'])) {
            $output->writeln('<comment>Found excluded paths. Setting them.</comment>');
            $this->_excluded_paths = $config['dump']['excluded_paths'];
        }

        if (array_key_exists('included_paths', $config['dump'])) {
            $output->writeln('<comment>Found included paths. Setting them.</comment>');
            $this->_included_paths = $config['dump']['included_paths'];
        }
    }
}