<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Signal;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractHandler
 *
 * Handle signal from the command line
 *
 * @category   Mothership
 * @package    Mothership_Magerun_Addons
 * @author     Maurizio Brioschi <brioschi@mothership.de>
 * @copyright  2016 Mothership Gmbh
 * @link       http://www.mothership.de/
 */
abstract class AbstractHandler implements HandleInterface
{
    /**
     * Output to the command line
     *
     * @var OutputInterface
     */
    protected $output;

    /**
     * Handler constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;

        declare(ticks = 1);

        $this->run();
    }

    /**
     * Handle the signal from the terminal
     *
     * @return void
     */
    public function run()
    {
        $this->configureSignals();
    }

    /**
     * Wait $seconds for the term signal (SIGTERM)
     *
     * @param int $seconds
     *
     * @return void
     */
    public function waitForTermSignal($seconds = 1)
    {
        $info = [];
        pcntl_sigtimedwait(array(SIGTERM), $info, $seconds);
    }

    /**
     * Signal to handle
     *
     * @return void
     */
    abstract public function configureSignals();
}
