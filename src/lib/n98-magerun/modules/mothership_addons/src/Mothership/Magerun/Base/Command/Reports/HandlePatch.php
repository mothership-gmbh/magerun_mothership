<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Reports;

use Mothership\Magerun\Patch\PatchInterface;
use Mothership\Magerun\Signal\AbstractHandler;

/**
 * Class HandleBasics
 * Handle for patch
 *
 * @category   Mothership
 * @package    Mothership_Magerun_Addons
 * @author     Maurizio Brioschi <brioschi@mothership.de>
 * @copyright  2016 Mothership Gmbh
 * @link       http://www.mothership.de/
 */
class HandlePatch extends AbstractHandler
{
    protected $patch;

    public function __construct(OutputInterface $output, PatchInterface $patch)
    {
        parent::__construct($output);
        if(is_null($patch)){
            throw new \Exception("Patch class can't be null");
        }
        $this->patch = $patch;
    }

    /**
     * Signal to handle
     *
     * @return void
     */
    public function configureSignals()
    {
        pcntl_signal(SIGINT, function ($signal) {
            $this->output->writeln("<info>Handle signal: " . $signal . "</info>");
            $this->stop();
        });

        pcntl_signal(SIGTSTP, function ($signal) {
            $this->output->writeln("<info>Handle signal: " . $signal . "</info>");
            $this->stop();
        });
    }

    /**
     * action done when stop handle
     *
     * @return void
     */
    function stop()
    {
        $this->output->writeln("<info>Removing patch</info>");
        $this->patch->removePatch();
        $this->output->writeln("<info>Stop reporting</info>");
        exit;
    }
}

