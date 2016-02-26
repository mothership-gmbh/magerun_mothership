<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Patch;
/**
 * Class AbstractMagentoPatchFactory
 *
 * @category   Mothership
 * @package    Mothership_Magerun_Addons
 * @author     Maurizio Brioschi <brioschi@mothership.de>
 * @copyright  2016 Mothership Gmbh
 * @link       http://www.mothership.de/
 */
abstract class AbstractMagentoPatchFactory
{
    /**
     * @var string (ex. 1.9.2.3)
     */
    protected $magentoVersion;
    /**
     * the patch to apply
     *
     * @var PatchInterface
     */
    protected $patch;

    /**
     * AbstractMagentoPatchFactory constructor.
     *
     * @param $magentoV
     */
    public function __construct($magentoV)
    {
        $this->magentoVersion = $magentoV;
    }

    /**
     * Get the class for the patch, in base of the Magento version
     *
     * @return void
     *
     * @throws \Exception if the patch is not set
     */
    abstract protected function setMagentoPatchClass();

    /**
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getPatch()
    {
        try {
            $this->setMagentoPatchClass();
            return $this->patch;
        } catch (\Exception $e) {
            throw $e;
        }

    }
}
