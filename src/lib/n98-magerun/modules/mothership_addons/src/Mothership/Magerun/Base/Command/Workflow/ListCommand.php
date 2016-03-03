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

use \Mothership\Magerun\Base\Command\AbstractMagentoCommand;


/**
 * Class ListCommand.
 *
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 *
 * @link      http://www.mothership.de/
 */
class ListCommand extends AbstractMagentoCommand
{
    protected $description = 'List all available workflows in the directory <root>/app/etc/mothership/workflows';

    /**
     * Command config
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $help
            = <<<HELP
Run a workflow file based on the configuration file.

- All workflow files must be located in "app/etc/mothership/workflows"!
- The workflow file is for example "app/etc/mothership/workflows/example.yaml"
- The command will only work when run in the base directory.

HELP;
        $this->setHelp($help);
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
        $this->_detectConfiguration($input, $output, $input_path);
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
    protected function _detectConfiguration(InputInterface $input, OutputInterface $output, $path)
    {
        /**
         * If the user sets the option environment variable, then try to find it.
         */
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

        $output->writeln(
            "Run it like: <comment>magerun mothership:base:workflow:run --config=" . $environment_files[$environment]
            . " --help</comment>"
        );

        return $file_name;
    }
}
