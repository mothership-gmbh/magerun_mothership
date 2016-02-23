<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Environment;

use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Mothership_Addons\Lib\File;


use \Mothership\Magerun\Base\Command\AbstractMagentoCommand;

/**
 * Class ImportCommand.
 *
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 *
 * @link      http://www.mothership.de/
 */
class ImportCommand extends AbstractMagentoCommand
{
    protected $description = 'Overwrite the core_config_data table with all settings defined in the settings.php';
    /**
     * @var \SimpleXMLElement
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
        parent::configure();
        $this->addOption(
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
            $this->_base_path = $this->getApplication()->getMagentoRootFolder() . '/app/etc/mothership/environments';

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

                    $currentValue = $this->_getStoreConfig($_config_data['scope'], $_config_data['scope_id'], $path);

                    $changed = 'no';
                    if ($currentValue != $_config_data['value']) {
                        $this->_setStoreConfig($_config_data['scope'], $_config_data['scope_id'], $path, $_config_data['value']);
                        $changed = 'yes';
                    }

                    $table[] = array (
                        'path'     => $path,
                        'old_value' => substr($currentValue, 0, 40),
                        'new_value' => substr($_config_data['value'], 0, 40),
                        'changed'   => $changed,
                    );
                }
            }

            $this->getHelper('table')->write($output, $table);
        };
    }

    /**
     * Get the store config based on plain database queries. This is safer then the native magento function
     *
     * @param string $scope    the scope, for example default or website
     * @param int    $scope_id the scope id works similar to the store id
     * @param string $path     the configuration path in the core_config_data
     *
     * @return string
     */
    protected function _getStoreConfig($scope, $scope_id, $path)
    {
        $dbh = $this->_getDatabaseConnection();
        $sql = 'SELECT value
                  FROM core_config_data
                  WHERE scope = :scope AND scope_id = :scope_id AND path = :path';

        $sth = $dbh->prepare($sql);
        $sth->execute(array(
                ':scope'    => $scope,
                ':scope_id' => $scope_id,
                ':path'     => $path
            )
        );
        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        return (isset($result['value'])) ? $result['value'] : '';
    }

    /**
     * Set the store config
     *
     * @param string $scope    the scope, for example default or website
     * @param int    $scope_id the scope id works similar to the store id
     * @param string $path     the configuration path in the core_config_data
     * @param string $value    the value for a specific combination of scope, scope_id and path
     *
     * @return bool
     */
    protected function _setStoreConfig($scope, $scope_id, $path, $value)
    {
        $dbh = $this->_getDatabaseConnection();
        $sql = 'SELECT config_id, value
                  FROM core_config_data
                  WHERE scope = :scope AND scope_id = :scope_id AND path = :path';

        $sth = $dbh->prepare($sql);
        $sth->execute(array(
                ':scope'    => $scope,
                ':scope_id' => $scope_id,
                ':path'     => $path
            )
        );
        $result = $sth->fetch(\PDO::FETCH_ASSOC);

        if (isset($result['config_id'])) {

            $dbh = $this->_getDatabaseConnection();
            $sql = 'UPDATE core_config_data
                        SET value = :value
                        WHERE scope = :scope AND scope_id = :scope_id AND path = :path';

            $sth = $dbh->prepare($sql);
            $sth->execute(array(
                    ':scope'    => $scope,
                    ':scope_id' => $scope_id,
                    ':path'     => $path,
                    ':value'    => $value
                )
            );

        } else {
            $dbh = $this->_getDatabaseConnection();
            $sql = 'INSERT INTO core_config_data(scope, scope_id, path, value)
                  VALUES (:scope, :scope_id, :path, :value)';

            $sth = $dbh->prepare($sql);
            $sth->execute(array(
                    ':scope'    => $scope,
                    ':scope_id' => $scope_id,
                    ':path'     => $path,
                    ':value'    => $value
                )
            );
          //  $sth->exec();
        }
    }
}