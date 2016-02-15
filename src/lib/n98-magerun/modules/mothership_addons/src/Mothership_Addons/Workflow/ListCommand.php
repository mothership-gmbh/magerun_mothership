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
 * PHP Version 5.4
 *
 * @category  Mothership
 * @package   Mothership_Addons
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.mothership.de/
 */

namespace Mothership_Addons\Workflow;

use Symfony\Component\Console\Input\InputOption;

/**
 * Class ListCommand
 *
 * @category  Mothership
 * @package   Mothership_Addons
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 * @link      http://www.mothership.de/
 *
 *            magerun intex:workflow:run --config=ProductImport.yaml
 *
 */
class ListCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    /**
     * Command config
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('mothership:workflow:list')
            ->setDescription('List all available workflows');

        $help = <<<HELP
Run a workflow file based on the configuration file.

- All workflow files must be located in "app/etc/mothership/workflows"!
- The workflow file is for example "app/etc/mothership/workflows/example.yaml"
- The command will only work when run in the base directory.

Example:

  mothership:workflow:run example.yaml

Get all workflows:

  mothership:workflow:run

HELP;
        $this->setHelp($help);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
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

            $input_path = $this->getApplication()->getMagentoRootFolder() . '/app/etc/mothership/workflows';
            $filename   = $this->_detectConfiguration($input, $output, $input_path);




            // load configuration
            // parse the yaml file

            /**
             * Crucial as this method depends on the composer vendor library or
             * psr-0/4 support in general
             */
            \Mage::dispatchEvent('add_spl_autoloader');
        };
    }

    /**
     * Helper method to set the arguments for the state machine
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return array
     */
    protected function getArguments(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        return [
            'input'  => $input,
            'output' => $output
        ];
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
        $path
    ) {
        /**
         * If the user sets the option environment variable, then try to find it.
         */
        $output->writeln('<info>Scanning folder ' . $path . ' for configuration files</info>');

        $environment_files = array ();
        foreach (glob($path . DIRECTORY_SEPARATOR . '*.yaml') as $_file) {
            $_part               = pathinfo($_file);
            $environment_files[] = $_part['basename'];
        }

        $dialog      = $this->getHelper('dialog');
        $environment = $dialog->select(
            $output,
            'Please select your feed configuration',
            $environment_files,
            0
        );
        $output->writeln('You have just selected: ' . $environment_files[$environment]);
        $file_name = $environment_files[$environment];


        $output->writeln("Run it like: <comment>magerun mothership:workflow:run --config=". $environment_files[$environment]. " --help</comment>");

        return $file_name;
    }
}
