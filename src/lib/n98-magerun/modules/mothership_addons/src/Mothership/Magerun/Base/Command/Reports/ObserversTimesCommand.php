<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Reports;

use Mothership\Magerun\Base\Command\PatchInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use \Mothership\Magerun\Base\Command\AbstractMagentoCommand;

/**
 * Class ObserversTimesCommand
 *
 * This command provide methods to retrieve a csv reports with all the events and relative observers called for each
 * Magento page with the execution time
 *
 * @category  Mothership
 * @package   Mothership_Reports
 * @author    Maurizio Brioschi <brioschi@mothership.de>
 * @copyright 2015 Mothership GmbH
 * @link      http://www.mothership.de/
 */
class ObserversTimesCommand extends AbstractMagentoCommand
{
    protected $observerlog_dir;
    protected $dateStart;
    protected $file_report = [];
    protected $timestampfile;
    /**
     * @var PatchInterface;
     */
    protected $patch;

    protected $description = 'Create a csv report with the execution workflow and observers execution times';

    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this->addOption(
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

        parent::execute($input, $output);
        $this->handleSygnal();
        $this->output = $output;
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

            $factory = new MagentoPatchFactory(\Mage::getVersion());
            $this->patch = $factory->getMagentoPatchClass();
            $this->patch->addPatch($this->getApplication()->getMagentoRootFolder());

            $this->output->writeln("<info>Start reporting...</info>");
            $this->output->writeln("<warning>Press Ctrl+z or Ctrl+c to stop!</warning>");

            $this->file_report = scandir($this->observerlog_dir);
            $this->timestampfile = filemtime($this->observerlog_dir . '/timestamp');
            if ($bootleneck) {
                while (true) {
                    $this->output->write("<info>.</info>");
                    $newfiles = scandir($this->observerlog_dir);
                    if (count($newfiles) > count($this->file_report)) {
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
        $this->patch->removePatch();
        $this->output->writeln("<info>Stop reporting</info>");
        exit;
    }


    /**
     * Analise bottleneck from all the files in the array and print the output on the terminal line
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
                        $bootlenecks[] = [$myline['1'], $myline['4'], $myline['3'], $myline['5']];
                    }
                }
            }
            $explodeFile = explode("_", $filelog);
            $url = str_replace(".log.csv", "", $filelog);
            $this->output->writeln("<info>Url: " . str_replace("_", "/", str_replace
                ($explodeFile[0] . "_" . $explodeFile[1], "", $url))
                . "</info>");
            $table = $this->getHelper('table');
            $table->setHeaders(['Observer', 'Model', 'Method', 'Time (ms)']);
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