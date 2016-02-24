<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Reports;
use Mothership\Magerun\Base\Command\Reports\MagentoPatch1_9_2_3;
use Mothership\Magerun\Base\Command\Reports\MagentoPatch1_9_2_2;
/**
 * Class AbstractMagentoPatchFactory
 *
 * @category   Mothership
 * @package    Mothership_Reports
 * @author     Maurizio Brioschi <brioschi@mothership.de>
 * @copyright  2016 Mothership Gmbh
 * @link       http://www.mothership.de/
 */
class AbstractMagentoPatchFactory
{
    protected $magentoVersion;
    protected $patchClass;

    /**
     * AbstractMagentoPatchFactory constructor.
     *
     * @param $magentoV
     */
    public function __construct($magentoV)
    {
        $this->magentoVersion = str_replace(".", "_", $magentoV);
    }

    /**
     * Set the class for the patch, all the patch must be in the subfolder patch/Magento
     * The method set the patch that match with the magento version
     *
     * @throws \Exception
     */
    protected function setPatchClass()
    {
        $magentoVersions = scandir(__DIR__ . '/patch/Magento');
        foreach ($magentoVersions as $dir) {
            if ($dir != '.' && $dir != '..' && is_dir(__DIR__ . '/patch/Magento/' . $dir)) {
                if ($dir == $this->magentoVersion) {
                    $this->patchClass = "MagentoPatch" . $this->magentoVersion;
                }
            }
        }

        if (is_null($this->patchClass)) {
            throw new \Exception("Patch Class for this Magento version is not found");
        }
    }

    /**
     * Get the class for the patch, in base of the Magento version
     *
     * @return Mothership\Magerun\Base\Command\Reports\PatchInterface
     */
    public function getMagentoPatchClass()
    {
        if (is_null($this->patchClass) || !isset($this->patchClass)) {
            $this->setPatchClass();
        }

        return new $this->patchClass();
    }
}
