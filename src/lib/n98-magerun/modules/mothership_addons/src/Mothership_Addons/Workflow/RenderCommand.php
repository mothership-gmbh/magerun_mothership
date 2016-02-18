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
 * @package   Mothership_Aigner
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.mothership.de/
 */

namespace Mothership_Addons\Workflow;

use \DivineOmega\CachetPHP\Factories\CachetInstanceFactory;
use Symfony\Component\Console\Input\InputOption;
use Mothership_Intex\AbstractCommand;

/**
 * Class RunnerCommand
 *
 * @category  Mothership
 * @package   Mothership_Intex
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 * @link      http://www.mothership.de/
 *
 *
 *            OPTIONS
 *
 *            --data_type=[full|incremental|stock]
 *
 *              The type of the intex data. You can use one of the fiven one. If you pass incremental, you may pass a
 *              range with the --range argument. The default will always be two days. This command is optional. If you
 *              do not pass this parameter, then no items will be downloaded.
 *
 *            --range=[0-9]+
 *
 *              This is optional and only useful if you pass --data_type=incremental
 *
 *            --render-graph
 *
 *              Does not need a value. Can be used to debug your workflows, which are based on state machines.
 *
 *            --clean-jobs
 *
 *              This command will be used to clean all the existing jobs in the queues. Can be useful in the
 *              development environment. The Implementation is in the ProductImport.php
 *
 *            EXAMPLE
 *
 *            "daily incremental imports from the last 5 days"
 *
 *            magerun intex:workflow:run --config=ProductImport.yaml --data_type=incremental --range=5
 *
 *            "daily full imports"
 *
 *            magerun intex:workflow:run --config=ProductImport.yaml --data_type=full
 *
 *            "daily stock imports"
 *
 *            magerun intex:workflow:run --config=ProductImport.yaml --data_type=stock
 *
 *            "Only products but no data download"
 *
 *            magerun intex:workflow:run --config=ProductImport.yaml

 *
 */
class RenderCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    /**
     * Command config
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('mothership:workflow:render')
            ->setDescription('Run a workflow')
            ->addOption(
                'config',
                null,
                InputOption::VALUE_REQUIRED,
                'The name of the configuration'
            )
        ;

        parent::configure();
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


            $input_path  = $this->getApplication()->getMagentoRootFolder() . '/app/etc/mothership/workflows';
            $filename    = $this->_detectConfiguration($input, $output, $input_path);

            // load configuration


            /**
             * Crucial as this method depends on the composer vendor library or
             * psr-0/4 support in general
             */
            \Mage::dispatchEvent('add_spl_autoloader');

            // get the config

            if (!file_exists($input_path . '/' . $filename)) {
                throw new \Exception('File ' . $input_path . '/' . $filename . ' does not exist');
            }

            // $input_interface = new \Mothership\Component\Feed\Input\InputMysqlData($this->_getDatabaseConnection());

            $stateMachine = new \Mothership\StateMachine\StateMachine($input_path . '/' . $filename);
            $output->writeln('<comment>Create PNG in in: ' . $input_path . '/' . $filename .'.png </comment>');
            $stateMachine->renderGraph($input_path . '/' . $filename .'.png');
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
