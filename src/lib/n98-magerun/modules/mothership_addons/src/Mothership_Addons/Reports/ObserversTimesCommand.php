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
 * @category  Mothership
 * @package   Mothership_{EXTENSION NAME}
 * @author    Maurizio Brioschi <brioschi@mothership.de>
 * @copyright Copyright (c) 2015 Mothership GmbH
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.mothership.de/
 */
namespace Mothership_Addons\Reports;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ObserversTimesCommand extends AbstractMagentoCommand
{
    protected $magento_root;
    protected $mage_php; //Mage.php original file from magento
    protected $app_php; //App.php original file from Magento

    protected function configure()
    {
        $this->setName('mothership:reports:observerstimes')
            ->setDescription('Create a csv report with the execution workflow with the execution time');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->handleSygnal();
        $this->output = $output;
        $this->magento_root = $this->getApplication()->getMagentoRootFolder();

        $this->detectMagento($output);
        if ($this->initMagento()) {
            $this->output->writeln("<info>Applying the patch</info>");
            $this->addPatch();
            $this->output->writeln("<info>Start reporting</info>");
            $this->output->writeln("<warning>Press Ctrl+z or Ctrl+c to stop</warning>");
            $info = array();

            pcntl_sigwaitinfo(array(SIGTERM),$info);
        }

        $this->output->writeln("<error>Init Magento fail</error>");

    }

    /**
     * Function call on SIGNTERM to stop reporting
     */
    public function stopObserver()
    {
        $this->output->writeln("<info>Removing patch</info>");
        $this->removePatch();
        $this->output->writeln("<info>Stop reporting</info>");
        exit;
    }

    /**
     * add the patch
     */
    protected function addPatch()
    {
        $patch = file_get_contents(dirname(__FILE__) . "/patch/observerstimes_dispatchEvent");
        $this->mage_php = file_get_contents($this->magento_root . '/app/Mage.php');

        $mage_log = str_replace("Varien_Profiler::start('DISPATCH EVENT:'", trim($patch) . "Varien_Profiler::start
        ('DISPATCH EVENT:'", $this->mage_php);
        file_put_contents($this->magento_root . "/app/Mage.php", $mage_log);

        $this->app_php = file_get_contents($this->magento_root . "/app/code/core/Mage/Core/Model/App.php");
        $app_log = str_replace("Varien_Profiler::start('OBSERVER: '",
            "\$startime=microtime(true);Varien_Profiler::start('OBSERVER: '", $this->app_php);

        $patch_observer = file_get_contents(dirname(__FILE__) . "/patch/observerstimes_observer");
        $app_log = str_replace("Varien_Profiler::stop('OBSERVER: '", $patch_observer . "Varien_Profiler::stop
            ('OBSERVER: '", $app_log);
        file_put_contents($this->magento_root . "/app/code/core/Mage/Core/Model/App.php", $app_log);

    }

    /**
     * remove the patch
     */
    protected function removePatch()
    {

        file_put_contents($this->magento_root . "/app/Mage.php", $this->mage_php);
        file_put_contents($this->magento_root . "/app/code/core/Mage/Core/Model/App.php", $this->app_php);
    }

    private function handleSygnal()
    {
        declare(ticks = 1);

        pcntl_signal(SIGINT, function ($signal) {
            $this->output->writeln("<info>Handle signal: ".$signal."</info>");
            $this->stopObserver();
        });

        pcntl_signal(SIGTSTP, function ($signal) {
            $this->output->writeln("<info>Handle signal: ".$signal."</info>");
            $this->stopObserver();
        });
    }
}