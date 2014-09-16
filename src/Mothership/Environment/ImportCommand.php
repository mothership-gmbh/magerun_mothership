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
use Mothership\Lib\File;

class ImportCommand extends AbstractMagentoCommand
{
    /**
     * @var SimpleXMLElement
     */
    protected $_xmlFile = null;

    /**
     * @var int
     */
    protected $_storeId = null;

    /**
     * @var string
     */
    protected $_domain  = null;

    protected $_base_path = '';

    /**
     * You can pass a env-option like --env=development
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('mothership:env:import')
            ->setDescription('Overwrite the core_config_data table with all settings defined in the settings.php')
            ->addOption(
                'env',
                null,
                InputOption::VALUE_REQUIRED,
                'The name of the environment'
            )
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
            $this->_base_path = __DIR__ . '/resource';

            /**
             * If the user sets the option environment variable, then try to find it.
             */
            if ($input->getOption('env')) {
                $file_name = $this->_base_path . '/environment_' . $input->getOption('env') . '.php';
                $output->writeln('<info>Option "' . $input->getOption('env') . '" set</info>');
                if (!file_exists($file_name)) {
                    $output->writeln('<comment>Configuration-File ' . $file_name . ' not found. Please create a configuration file. You can run mothership:env:dump for creating a template.</comment>');
                } else {
                    $output->writeln('<info>Configuration-File ' . $file_name . ' found</info>');
                }
            } else {
                $output->writeln('<info>Scanning folder ' . $this->_base_path . ' for configuration files</info>');

                $environment_files = array();
                foreach (glob($this->_base_path . DIRECTORY_SEPARATOR . 'environment*.php') as $_file) {
                    $_part          = pathinfo($_file);
                    $_part_filename = explode('_', $_part['filename']);
                    $environment_files[] = $_part_filename[1];
                }

                $dialog = $this->getHelper('dialog');
                $environment = $dialog->select(
                    $output,
                    'Please select your environment',
                    $environment_files,
                    0
                );
                $output->writeln('You have just selected: ' . $environment_files[$environment]);
                $file_name = $this->_base_path . '/environment_' . $environment_files[$environment] . '.php';
            }


            $this->writeSection($output, 'Applying configuration');
            $config = File::loadConfig($file_name);

            $table = array();
            foreach ($config as $path => $data) {

                foreach ($data as $_config_data) {
                    $currentValue = \Mage::getStoreConfig($path, $_config_data['scope_id']);

                    //\Mage::app()->getConfig()->saveConfig($values['path'], $values['value'], $values['scope'], $storeId);
                    $table[] = array (
                        'path'     => $path,
                        'old_value' => substr($currentValue, 0, 40),
                        'new_value' => substr($_config_data['value'], 0, 40)
                    );
                }
            }

            $this->getHelper('table')->write($output, $table);
        };
    }
}