<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Reports\Lib\Patch;
/**
 * Class MagetoPatch1922
 *
 * @category   Mothership
 * @package    Mothership_Reports
 * @author     Maurizio Brioschi <brioschi@mothership.de>
 * @copyright  2016 Mothership Gmbh
 * @link       http://www.mothership.de/
 */
class MagentoPatch1922 extends AbstractMagentoPatch
{

    /**
     * add the patch
     *
     * @param $magentoRoot
     *
     * @return void
     */
    function addPatch($magentoRoot)
    {
        parent::addPatch($magentoRoot);

        $patch_mageRun = file_get_contents($this->pathDir . "/patch/observerstimes_mageRun");
        $patch_mageRunEnd = file_get_contents($this->pathDir . "/patch/observerstimes_mageRunEnd");

        $mage_log = str_replace("Varien_Profiler::start('mage');", "Varien_Profiler::start('mage');" . $patch_mageRun,
            $this->mage_php);
        $mage_log = str_replace("Varien_Profiler::stop('mage');", $patch_mageRunEnd . "Varien_Profiler::stop('mage');",
            $mage_log);

        file_put_contents($this->magento_root . "/app/Mage.php", $mage_log);

        $app_log = str_replace("Varien_Profiler::start('OBSERVER: '",
            "\$startime=microtime(true);Varien_Profiler::start('OBSERVER: '", $this->app_php);

        $patch_observer = file_get_contents($this->pathDir . "/patch/observerstimes_observer");

        $app_log = str_replace("Varien_Profiler::stop('OBSERVER: '", $patch_observer . "Varien_Profiler::stop
            ('OBSERVER: '", $app_log);
        file_put_contents($this->magento_root . "/app/code/core/Mage/Core/Model/App.php", $app_log);
    }

    /**
     * Set the path directory of the patch
     *
     * @return void
     */
    protected function setPathDirectory()
    {
        return __DIR__ . '/patches/Magento/1_9_2_2';
    }
}
