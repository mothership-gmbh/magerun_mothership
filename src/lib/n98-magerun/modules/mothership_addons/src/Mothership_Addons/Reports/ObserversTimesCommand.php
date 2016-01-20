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
 * @package   Mothership_Reports
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

/**
 * This command provide methods to retrieve a csv reports with all the events and relative observers called for each
 * Magento page with the execution time
 * @package Mothership_Addons\Reports
 */
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
            ->setDescription('Create a csv report with the execution workflow and observers execution times')
            ->addOption(
                'bootleneck',
                null,
                InputOption::VALUE_OPTIONAL,
                'if set you have a detail analisys of the most expensive observers'
            );
    }

    /**
     * Execute the command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
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

            $this->file_report = scandir($this->observerlog_dir);
            $this->timestampfile = filemtime($this->observerlog_dir . '/timestamp');
            if ($bootleneck) {
                /**
                 * infinite loop waiting for signal from the terminal
                 */
                while (true) {
                    $this->output->write("<info>.</info>");
                    $newfiles = scandir($this->observerlog_dir);
                    //if we surf the website we add a new file, so we have to analyze it
                    if (count($newfiles) > count($this->file_report)) {
                        $this->checkBootleneck($newfiles);
                    }
                    //wait one second
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
     * add patches from folder patch
     */
    protected function addPatch()
    {
        //patch for /app/Mage.php
        $this->mage_php = file_get_contents($this->magento_root . '/app/Mage.php');

        $patch_mageRun = file_get_contents(dirname(__FILE__) . "/patch/observerstimes_mageRun");
        $patch_mageRunEnd = file_get_contents(dirname(__FILE__) . "/patch/observerstimes_mageRunEnd");

        $mage_log = str_replace("Varien_Profiler::start('mage');", "Varien_Profiler::start('mage');" . $patch_mageRun,
            $this->mage_php);
        $mage_log = str_replace("Varien_Profiler::stop('mage');", $patch_mageRunEnd . "Varien_Profiler::stop('mage');",
            $mage_log);
        file_put_contents($this->magento_root . "/app/Mage.php", $mage_log);

        //patch for /app/code/core/Mage/Core/Model/App.php
        $this->app_php = file_get_contents($this->magento_root . "/app/code/core/Mage/Core/Model/App.php");
        $app_log = str_replace("Varien_Profiler::start('OBSERVER: '",
            "\$startime=microtime(true);Varien_Profiler::start('OBSERVER: '", $this->app_php);

        $patch_observer = file_get_contents(dirname(__FILE__) . "/patch/observerstimes_observer");

        $app_log = str_replace("Varien_Profiler::stop('OBSERVER: '", $patch_observer . "Varien_Profiler::stop
            ('OBSERVER: '", $app_log);
        file_put_contents($this->magento_root . "/app/code/core/Mage/Core/Model/App.php", $app_log);

    }

    /**
     * remove patches
     */
    protected function removePatch()
    {
        file_put_contents($this->magento_root . "/app/Mage.php", $this->mage_php);
        file_put_contents($this->magento_root . "/app/code/core/Mage/Core/Model/App.php", $this->app_php);
    }

    /**
     * Analise bottleneck from all the files in the array and print the output on the terminal line
     * The analysis content is caused by the patch inserted
     * @param array $newfiles
     */
    protected function checkBootleneck(array $newfiles)
    {
        $diff = array_diff($newfiles, $this->file_report);
        foreach ($diff as $filelog) {
            $this->output->writeln("<info>Analisyng: " . $filelog . "</info>");
            $csvData = $this->getFileContent($filelog);
            $lines = explode(PHP_EOL, $csvData);
            $bootlenecks = [];
            foreach ($lines as $line) {
                $myline = str_getcsv($line, ";");
                if (count(array_keys($myline)) > 4) {
                    if ($myline[5] > 0) {
                        //HEADER -> ["EVENT","OBSERVER","TYPE","METHOD","MODEL","ARGS","TIME(ms)"]
                        $bootlenecks[] = [$myline['1'], $myline['5'], $myline['3'], $myline['5'], $myline['6']];
                    }
                }
            }
            $explodeFile = explode("_", $filelog);
            $url = str_replace(".log.csv", "", $filelog);
            $this->output->writeln("<info>Url: " . str_replace("_", "/", str_replace
                ($explodeFile[0] . "_" . $explodeFile[1], "", $url))
                . "</info>");
            $table = $this->getHelper('table');
            $table->setHeaders(['Observer', 'Model', 'Method', 'Args', 'Time (ms)']);
            $table->setRows($bootlenecks);
            $table->render($this->output);
        }
        $this->file_report = $newfiles;
    }

    /**
     * Try to get the contents of a report files, if it's lock from Magento, sleep for 1 sec.
     * @param $filename
     * @return string
     */
    protected function getFileContent($filename)
    {
        $fp = fopen($this->observerlog_dir . '/' . $filename, 'r');
        if (!$fp) {
            sleep(1);
            $this->getFileContent($filename);
        } else if (flock($fp, LOCK_EX)) {
            $csvData = file_get_contents($this->observerlog_dir . '/' . $filename);
            fflush($fp);
            flock($fp, LOCK_UN);
            return $csvData;
        } else {
            sleep(1);
            $this->getFileContent($filename);
        }
    }

    /**
     * Handle the signal from the terminal
     */
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


}
