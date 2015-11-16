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
use Symfony\Component\Console\Input\InputOption;
use Varien_Io_File;

class ObserversTimesCommand extends AbstractMagentoCommand
{
    protected $magento_root;
    protected $observerlog_dir;
    protected $mage_php; //Mage.php original file from magento
    protected $app_php; //App.php original file from Magento
    protected $dateStart;
    protected $file_report = [];
    protected $timestampfile;

    protected function configure()
    {
        $this->setName('mothership:reports:observerstimes')
            ->setDescription('Create a csv report with the execution workflow and observers execution time')
            ->addOption(
                'bootleneck',
                null,
                InputOption::VALUE_OPTIONAL,
                'if set you have a detail analisys of the most expensive observers'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->handleSygnal();
        $this->output = $output;
        $this->magento_root = $this->getApplication()->getMagentoRootFolder();
        $this->observerlog_dir = $this->magento_root . "/observerlogs";
        if (!file_exists($this->observerlog_dir)) {
            mkdir($this->observerlog_dir, 0777);
        }
        $this->dateStart = new \DateTime();
        $bootleneck = false;
        $info = array();

        if ($input->getOption('bootleneck') == true) {
            $bootleneck = true;
            $this->output->writeln("<info>I will report bootlenecks</info>");
        }

        $this->detectMagento($output);
        if ($this->initMagento()) {
            $this->output->writeln("<info>Applying the patch...</info>");
            $this->addPatch();
            $this->output->writeln("<info>Start reporting...</info>");
            $this->output->writeln("<warning>Press Ctrl+z or Ctrl+c to stop!</warning>");


            touch($this->observerlog_dir.'/timestamp',time()-3600);
            chmod($this->observerlog_dir.'/timestamp',0777);
            $this->file_report = scandir($this->observerlog_dir);
            $this->timestampfile = filemtime($this->observerlog_dir.'/timestamp');
            if ($bootleneck) {
                while (true) {
                    $this->output->write("<info>.</info>");
                    if (fileatime($this->observerlog_dir.'/timestamp') > $this->timestampfile) {
                        $newfiles = scandir($this->observerlog_dir);
                        $this->checkBootleneck($newfiles);
                    }

                    pcntl_sigtimedwait(array(SIGTERM), $info, 1);
                }
            } else {
                pcntl_sigtimedwait(array(SIGTERM), $info, 1);
            }


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
    protected function addPatch($bootleneck = false)
    {
        //$patch = file_get_contents(dirname(__FILE__) . "/patch/observerstimes_dispatchEvent");
        $this->mage_php = file_get_contents($this->magento_root . '/app/Mage.php');

        /*$mage_log = str_replace("Varien_Profiler::start('DISPATCH EVENT:'", trim($patch) . "Varien_Profiler::start
        ('DISPATCH EVENT:'", $this->mage_php);*/

        $patch_mageRun = file_get_contents(dirname(__FILE__) . "/patch/observerstimes_mageRun");
        $patch_mageRunEnd = file_get_contents(dirname(__FILE__) . "/patch/observerstimes_mageRunEnd");

        $mage_log = str_replace("Varien_Profiler::start('mage');","Varien_Profiler::start('mage');".$patch_mageRun,
            $this->mage_php);
        $mage_log = str_replace("Varien_Profiler::stop('mage');",$patch_mageRunEnd."Varien_Profiler::stop('mage');",
            $mage_log);

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
     * remove the patchcount(scandir($this->magento_root . '/var/log'));
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
            $this->output->writeln("<info>Handle signal: " . $signal . "</info>");
            $this->stopObserver();
        });

        pcntl_signal(SIGTSTP, function ($signal) {
            $this->output->writeln("<info>Handle signal: " . $signal . "</info>");
            $this->stopObserver();
        });
    }


    protected function checkBootleneck(array $newfiles)
    {
        $diff = array_diff($newfiles, $this->file_report);
        foreach ($diff as $filelog) {
            $this->output->writeln("<info>Analisyng: " . $filelog . "</info>");
            $csvData = file_get_contents($this->observerlog_dir . '/' . $filelog);
            $lines = explode(PHP_EOL, $csvData);
            $bootlenecks = [];
            foreach ($lines as $line) {
                $myline = str_getcsv($line, ";");
                print_r($myline);
                if ($myline[5] > 0) {
                    $this->output->writeln("<info>CIAO: ".$myline['5']."</info>");
                    $bootlenecks[] = [$myline['1'], $myline['4'], $myline['3'], $myline['5']];
                }
            }

            $filenameEx = explode("_", $filelog);
            /*$url = str_replace(".log.csv", "",$filenameEx[2]);
            $this->output->writeln("<info>Url: ".$url."</info>");*/
            $table = $this->getHelper('table');
            $table->setHeaders(['Observer', 'Model', 'Method', 'Time (ms)']);
            $table->setRows($bootlenecks);
            $table->render($this->output);
        }
        $this->file_report = $newfiles;
        $this->timestampfile =fileatime($this->observerlog_dir.'/timestamp');
    }

}