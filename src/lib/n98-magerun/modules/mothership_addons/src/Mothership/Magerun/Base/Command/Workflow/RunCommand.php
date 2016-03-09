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
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Question\ChoiceQuestion;
use \Symfony\Component\Console\Question\Question;
use \Symfony\Component\Yaml\Yaml;

use \Mothership\Magerun\Base\Command\AbstractMagentoCommand;

/**
 * Class RunCommand.
 *
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 *
 * @link      http://www.mothership.de/
 */
class RunCommand extends AbstractMagentoCommand
{
    /**
     * The workflow configuration files is an array which has been
     * loaded from a configuration in app/etc/mothership/workflow
     *
     * @var mixed
     */
    protected $workflowConfiguration;

    /**
     * Hold additional configuration from the workflow files.
     *
     * @var mixed
     */
    protected $parsedYaml = [];

    protected $description = 'Run a workflow';

    /**
     * Command config
     *
     * @return void
     */
    protected function configure()
    {
         parent::configure();

        if (isset($GLOBALS['argv'][2])) {

            $explodedConfig = explode("=", $GLOBALS['argv'][2]);

            /**
             * Add the option to add this to a queue. Requires a queue configuration
             */
            $this->addOption(
                'queue',
                null,
                1,
                'Process the workflow in php-resque. <comment>Enable the queue in the Magento backend!</comment>'
            );

            // add the config
            $this->addOption(
                'config',
                'c',
                2,
                'configuration'
            );

            if (array_key_exists(1, $explodedConfig) && strpos($explodedConfig[1], 'yaml') !== false) {

                $workflowConfigurationFile = getcwd() . '/app/etc/mothership/workflows/' . $explodedConfig[1];

                if (file_exists($workflowConfigurationFile)) {
                    $this->parsedYaml = Yaml::parse(file_get_contents($workflowConfigurationFile));

                    if (array_key_exists('options', $this->parsedYaml)) {

                        // add the config
                        $this->addOption(
                            'interactive',
                            'i',
                            InputOption::VALUE_NONE,
                            'enables the interactive mode'
                        );

                        $this->workflowConfiguration = $this->parsedYaml['options'];

                        foreach ($this->parsedYaml['options'] as $_option => $_optionConfiguration) {
                            $this->addOption(
                                $_option,
                                array_key_exists('short', $_optionConfiguration) ? $_optionConfiguration['short'] : null,
                                $_optionConfiguration['required'],
                                $_optionConfiguration['description'],
                                array_key_exists('default', $_optionConfiguration) ? $_optionConfiguration['default'] : null
                            );
                        }
                    }
                }
            }
        }

        $help = <<<HELP
Run a workflow file based on the configuration file.

- All workflow files must be located in "app/etc/mothership/workflows"!
- The workflow file is for example "app/etc/mothership/workflows/example.yaml"
- The command will only work when run in the base directory.

Example:

  mothership:workflow:run --config=example.yaml

Get all workflows:

  mothership:workflow:run

Get help for a specific workflow

 magerun mothership:workflow:run --config=IntexDataImport.yaml --help

HELP;
        $this->setHelp($help);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasOption('interactive') && $input->getOption('interactive')) {

            $output->writeln('<info>Interactive Mode enabled</info>');

            $questionHelper = $this->getHelper('question');

            foreach ($this->workflowConfiguration as $_options => $_config) {

                $defaultValue = '';
                if (array_key_exists('default', $_config)) {
                    $defaultValue = ' <comment>(default=' . $_config['default'] . ')</comment>';
                }

                $questionText = "\n<fg=cyan>" . $_config['description'] . $defaultValue . "</>\n";


                if (array_key_exists('options', $_config)) {

                    $question = new ChoiceQuestion(
                        $questionText,
                        $_config['options'],
                        array_key_exists('default', $_config) ? array_search($_config['default'], $_config['options']) : null
                    );

                    $answer = $questionHelper->ask($input, $output, $question);
                } else {
                    $question = new Question(
                        $questionText,
                        array_key_exists('default', $_config) ? $_config['default'] : null
                    );
                    $answer = $questionHelper->ask($input, $output, $question);
                }

                $input->setOption($_options, $answer);
                $output->writeln('<comment>Your answer: </comment>' . $answer);
            }
        }
    }

    /**
     * The command
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $input_path = $this->getApplication()->getMagentoRootFolder() . '/app/etc/mothership/workflows';
        $filename   = $this->detectConfiguration($input, $output, $input_path);

        if ($input->hasOption('queue') && $input->getOption('queue')) {
            /**
             * Add the job for the incremental update
             */
            $args = array_merge([
                'workflow_path' => $this->getApplication()->getMagentoRootFolder() . '/app/etc/workflows/' . $input->getOption('config'),
                'workflow'      => $input->getOption('config'),
            ], $this->getArguments($input, $output));

            $this->printCommand($args, $output);

            // TODO: Check if queue is enabled
            \Resque::setBackend(\Mage::getStoreConfig('mothership_magerun/queue/host'));
            \Resque::enqueue(\Mage::getStoreConfig('mothership_magerun/queue/name'), '\Mothership\Magerun\Queue\Jobs\General', $args, true);
        } else {
            $configuration = Yaml::parse(file_get_contents($input_path . '/' . $filename));
            $stateMachine = new \Mothership\StateMachine\StateMachine($configuration);
            $stateMachine->run($this->getArguments($input, $output));
        }
    }

    /**
     * @return void
     */
    protected function printCommand($args, $output)
    {
        $data = [];
        foreach ($args as $_arg => $_value) {
            if (!in_array($_arg, ['input', 'output', 'interactive', 'workflow_path', 'workflow']) && false !== $_value) {
                $data[$_arg] = $_value;
                echo "\n" . $_arg . "=> " . $_value;
            }
        }

        $ordered = array_merge(array_flip(array('config', 'environment', 'data_type', 'range', 'root-dir', 'queue')), $data);
        $output->writeln("\n" . '<comment>magerun mothership:workflow:run --' .  implode(' --', array_map(
                    function ($v, $k) { return $k . '=' . $v; },
                    $ordered,
                    array_keys($ordered)
                )) . '</comment>');
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
        return array_merge($input->getOptions(), [
            'input'    => $input,
            'output'   => $output,
            'root-dir' => $this->getApplication()->getMagentoRootFolder(),
            'yaml'     => $this->parsedYaml
        ]) ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string                                            $path
     *
     * @return string
     */
    protected function detectConfiguration(InputInterface $input, OutputInterface $output, $path) {
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

        }

        return $file_name;
    }
}
