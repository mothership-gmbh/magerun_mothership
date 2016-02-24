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
 * Class MagetoPatch1_9_2_2
 *
 * @category   Mothership
 * @package    Mothership_Reports
 * @author     Maurizio Brioschi <brioschi@mothership.de>
 * @copyright  2016 Mothership Gmbh
 * @link       http://www.mothership.de/
 */
class MagentoPatch1_9_2_2 implements PatchInterface
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
        $this->magento_root = $magentoRoot;

        $this->mage_php = file_get_contents($this->magento_root . '/app/Mage.php');

        $patch_mageRun = file_get_contents(dirname(__FILE__) . "/patch/observerstimes_mageRun");
        $patch_mageRunEnd = file_get_contents(dirname(__FILE__) . "/patch/observerstimes_mageRunEnd");

        $mage_log = str_replace("Varien_Profiler::start('mage');", "Varien_Profiler::start('mage');" . $patch_mageRun,
            $this->mage_php);
        $mage_log = str_replace("Varien_Profiler::stop('mage');", $patch_mageRunEnd . "Varien_Profiler::stop('mage');",
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
     * remove patch
     * @return void
     */
    function removePatch()
    {
        file_put_contents($this->magento_root . "/app/Mage.php", $this->mage_php);
        file_put_contents($this->magento_root . "/app/code/core/Mage/Core/Model/App.php", $this->app_php);
    }
}
