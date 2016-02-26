<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership_Addons\Patch;

/**
 * Class AbstractMagentoPatch
 *
 * @category   Mothership
 * @package    Mothership_Magerun_Addons
 * @author     Maurizio Brioschi <brioschi@mothership.de>
 * @copyright  2016 Mothership Gmbh
 * @link       http://www.mothership.de/
 */
abstract class AbstractMagentoPatch implements PatchInterface
{
    protected $pathDir;

    protected $magento_root;
    protected $mage_php; //Mage.php original file from magento
    protected $app_php; //App.php original file from Magento

    public function __construct()
    {
        $this->pathDir = $this->setPathDirectory();
    }

    /**
     * add the patch
     *
     * @param $magentoRoot
     *
     * @return void
     */
    function addPatch($magentoRoot)
    {
        $this->storeOriginalFilesPatched($magentoRoot);
    }

    /**
     * remove patch
     *
     * @return void
     *
     * @throws \Exception
     */
    function removePatch()
    {
        if (is_null($this->magento_root)) {
            throw new \Exception("Patch is not added to Magento yet");
        }

        file_put_contents($this->magento_root . "/app/Mage.php", $this->mage_php);
        file_put_contents($this->magento_root . "/app/code/core/Mage/Core/Model/App.php", $this->app_php);
    }

    /**
     * Store original files content before to add the patch
     *
     * @param $magentoRoot
     *
     * @throws \Exception
     */
    protected function storeOriginalFilesPatched($magentoRoot)
    {
        $this->magento_root = $magentoRoot;

        if (!file_exists($this->magento_root . "/app/Mage.php")) {
            throw new \Exception($this->magento_root . "/app/Mage.php doesn't exist");
        }

        if (!file_exists($this->magento_root . "/app/code/core/Mage/Core/Model/App.php")) {
            throw new \Exception($this->magento_root . "/app/code/core/Mage/Core/Model/App.php doesn't exist");
        }

        $this->magento_root = $magentoRoot;
        $this->mage_php = file_get_contents($this->magento_root . '/app/Mage.php');
        $this->app_php = file_get_contents($this->magento_root . "/app/code/core/Mage/Core/Model/App.php");

    }

    /**
     * Set the path directory of the patch
     *
     * @return void
     */
    abstract protected function setPathDirectory();
}
