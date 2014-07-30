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

    /**
     * Command config
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('mothership:env:import')
            ->setDescription('Setzt die Variablen für die Test bzw. Entwicklungsumgebung.')
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

            $this->writeSection($output, 'Setting Config for Test-Environment');


            $config = File::loadConfig(__DIR__ . '/resource/dump.php');

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