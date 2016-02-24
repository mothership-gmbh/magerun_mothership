<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Reports;

use Mothership\Magerun\Base\Command\PatchInterface;

/**
 * Class MagetoPatch1_9_2_3
 *
 * @category   Mothership
 * @package    Mothership_Reports
 * @author     Maurizio Brioschi <brioschi@mothership.de>
 * @copyright  2016 Mothership Gmbh
 * @link       http://www.mothership.de/
 */
class MagentoPatch1_9_2_3 implements PatchInterface
{
    protected $magento_root;
    protected $mage_php; //Mage.php original file from magento
    protected $app_php; //App.php original file from Magento

    /**
     * add the patch
     *
     * @param $magentoRoot
     *
     * @return void
     */
    function addPatch($magentoRoot)
    {
        die('\nMairi');
    }

    /**
     * remove patch
     * @return void
     */
    function removePatch()
    {
        file_put_contents($this->magento_root . "/app/Mage.php", $this->mage_php);
        file_put_contents($this->magento_root . "/app/code/core/Mage/Core/Model/App.php", $this->app_php);
    }
}
