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

namespace Mothership_Addons\Feed;

use Mothership_Addons\Lib\Database;
use Mothership_Addons\Lib\File;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Console\Input\InputOption;
use Mothership\Component\Feed\Output\OutputCsv;

/**
 * Class CleanCommand
 *
 * @category  Mothership
 * @package   Mothership_ExportCommand
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2015 Mothership GmbH
 * @link      http://www.mothership.de/
 */
class ExportCommand extends Database
{
    /**
     * Command config
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('mothership:feed:export')
            ->setDescription('Export')
            ->addOption(
                'config',
                null,
                InputOption::VALUE_REQUIRED,
                'The name of the configuration'
            )
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        $this->detectMagento($output);
        if ($this->initMagento()) {


            $input_path  = $this->getApplication()->getMagentoRootFolder() . '/app/etc/mothership/feeds';
            $output_path = $this->getApplication()->getMagentoRootFolder() . '/media/feeds/';

            $filename = $this->_detectConfiguration($input, $output, $input_path);

            /**
             * Crucial as this method depends on the composer vendor library or
             * psr-0/4 support in general
             */
            \Mage::dispatchEvent('add_spl_autoloader');

            /**
             * Initialize the database settings and store them in a seperate array
             * which is used to overwrite the database configuration in the feed configuration file later
             */
            $this->detectDbSettings($output);

            $config  = [
                'input' => [
                    'type' => 'sql'
                ],
                'db' => [
                    'host'     => (string) $this->dbSettings['host'],
                    'username' => (string) $this->dbSettings['username'],
                    'password' => (string) $this->dbSettings['password'],
                    'database' => (string) $this->dbSettings['dbname'],
                    'port'     => 3306,
                ]
            ];

            if (!file_exists($input_path . '/' . $filename)) {
                throw new \Exception('File ' . $input_path . '/' . $filename . ' does not exist');
            }

            $factory = new FeedFactory($input_path . '/' . $filename, $config);
            $factory->processFeed(new OutputCsv($output_path . $filename . '.csv'));

            $output->writeln('<comment>File saved to : ' . $output_path . $filename . '.csv' . '</comment>');
        };
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string                                            $path
     *
     * @return string
     */
    protected function _detectConfiguration(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output,
        $path)
    {
        /**
         * If the user sets the option environment variable, then try to find it.
         */
        if ($input->getOption('config')) {
            $file_name = $input->getOption('config');
            $full_path = $path . DIRECTORY_SEPARATOR . $input->getOption('config');
            $output->writeln('<info>Option "' . $input->getOption('config') . '" set</info>');
            if (!file_exists($full_path)) {
                $output->writeln('<comment>Configuration-File ' . $full_path . ' not found. .</comment>');
            } else {
                $output->writeln('<info>Configuration-File ' . $full_path . ' found</info>');
            }
        } else {
            $output->writeln('<info>Scanning folder ' . $path . ' for configuration files</info>');

            $environment_files = array();
            foreach (glob($path . DIRECTORY_SEPARATOR . '*.yaml') as $_file) {
                $_part          = pathinfo($_file);
                $environment_files[] = $_part['basename'];
            }

            $dialog = $this->getHelper('dialog');
            $environment = $dialog->select(
                $output,
                'Please select your feed configuration',
                $environment_files,
                0
            );
            $output->writeln('You have just selected: ' . $environment_files[$environment]);
            $file_name = $environment_files[$environment];
        }
        return $file_name;
    }
}
