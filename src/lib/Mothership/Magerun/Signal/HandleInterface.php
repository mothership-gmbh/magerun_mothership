<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Signal;
/**
 * Class HandleInterface
 *
 * @category   Mothership
 * @package    Mothership_Magerun_Addons
 * @author     Maurizio Brioschi <brioschi@mothership.de>
 * @copyright  2016 Mothership Gmbh
 * @link       http://www.mothership.de/
 */
interface HandleInterface
{
    /**
     * Start the handler
     * @return void
     */
    function run();

    /**
     * action done when stop handle
     *
     * @return void
     */
    function stop();
}
