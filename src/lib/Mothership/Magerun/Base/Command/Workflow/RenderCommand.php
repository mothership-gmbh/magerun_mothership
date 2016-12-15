<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Workflow;

use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Input\InputOption;

use \Mothership\Magerun\Base\Command\AbstractMagentoCommand;
use \Symfony\Component\Yaml\Yaml;


/**
 * Class RenderCommand.
 *
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 *
 * @link      http://www.mothership.de/
 */
class RenderCommand extends AbstractMagentoCommand
{
    protected $description = 'Render a workflow as a graph. Based on graphviz.';
    /**
     * Command config
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this->addOption(
                'config',
                null,
                InputOption::VALUE_REQUIRED,
                'The name of the configuration'
            );
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $input_path = $this->getApplication()->getMagentoRootFolder() . '/app/etc/mothership/workflows';
        $filename   = $this->_detectConfiguration($input, $output, $input_path);


        // get the config

        if (!file_exists($input_path . '/' . $filename)) {
            throw new \Exception('File ' . $input_path . '/' . $filename . ' does not exist');
        }

        // $input_interface = new \Mothership\Component\Feed\Input\InputMysqlData($this->_getDatabaseConnection());

        $configuration = Yaml::parse(file_get_contents($input_path . '/' . $filename));
        $stateMachine = new \Mothership\StateMachine\StateMachine($configuration);
        $output->writeln('<comment>Create PNG in in: ' . $input_path . '/' . $filename . '.png </comment>');
        $stateMachine->renderGraph($input_path . '/' . $filename . '.png');

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

            $environment_files = [];
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
        }

        return $file_name;
    }
}
