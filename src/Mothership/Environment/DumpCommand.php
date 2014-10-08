<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * PHP Version 5.3
 *
 * @category  Mothership
 * @package   Mothership_Shell
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2013 Mothership GmbH
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.mothership.de/
 */

namespace Mothership\Environment;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\OperatingSystem;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Finder\Finder;
use Mothership\Lib\Database;
use Mothership\Lib\File;
use Mothership\Lib\Logger;

class DumpCommand extends Database
{
    /**
     * The excluded paths are all paths in the table core_config_data
     * which are ignored by the dump. You should set them in the /resource/config.php
     *
     * @var array
     */
    protected $_excluded_paths;

    /**
     * The configuration which should be exported
     *
     * @var mixed
     */
    protected $_config_to_be_exported;

    /**
     * Command config
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('mothership:env:dump')
            ->setDescription('Dump all settings from the core_config_data and save them in the file settings.php')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = Logger::getInstance();

        $this->detectMagento($output);
        if ($this->initMagento()) {


            $this->writeSection($output, 'Import the settings from the settings.php file');

            $this->_initConfig($output);


            $pdo = $this->_getDatabaseConnection();

            $query = "SELECT * FROM core_config_data";
            $sth = $pdo->prepare($query);
            $sth->execute();
            $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

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
                }
                Logger::logCounter('total');
            }

            // dump the log
            $logger->getData($this, $output);
            File::writePHPArray(__DIR__ . '/resource/dump.php', $this->_config_to_be_exported);

        };
    }

    /**
     * Load the configuration and set the general values
     */
    protected function _initConfig($output)
    {
        $config = File::loadConfig(__DIR__ . '/resource/config.php');

        if (array_key_exists('excluded_paths', $config['dump'])) {
            $output->writeln('<comment>Found excluded paths. Setting them.</comment>');
            $this->_excluded_paths = $config['dump']['excluded_paths'];
        }
    }
}