<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Reports;
/**
 * Class AbstractPatch
 *
 * @category   Mothership
 * @package    Mothership_
 * @author     Maurizio Brioschi <brioschi@mothership.de>
 * @copyright  2016 Mothership Gmbh
 * @link       http://www.mothership.de/
 */
Interface PatchInterface
{
    /**
     * add the patch
     *
     * @param $magentoRoot
     *
     * @return void
     */
    function addPatch($magentoRoot);

    /**
     * remove patch
     * @return void
     */
    function removePatch();

}

