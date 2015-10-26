<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * PHP Version 5.3
 *
 * @category  Mothership
 * @package   Mothership_Shell
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2013 Mothership GmbH
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.mothership.de/
 */

namespace Mothership_Addons\Feed;

use Mothership_Addons\Images\AbstractCommand;
use Mothership_Addons\Lib\File;


/**
 * Class CleanCommand
 *
 * @category  Mothership
 * @package   Mothership_ExportCommand
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2015 Mothership GmbH
 * @link      http://www.mothership.de/
 */
class ExportCommand extends AbstractCommand
{
    /**
     * Command config
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('mothership:feed:export')
            ->setDescription('Remove unused images from the catalog/product directory');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        $this->detectMagento($output);
        if ($this->initMagento()) {

            $base_path = $this->getApplication()->getMagentoRootFolder() . '/var/export';

            \Mage::dispatchEvent('add_spl_autoloader');

            /* @var \Mothership_Addons_Helper_Product $data */
            $data = \Mage::helper('mothership_addons/product');

            $c = $data->export();

            file_put_contents($base_path . '/dump.php',  '<?php return ' . var_export($c, true) . ';');

            $dump = include_once $base_path . '/dump.php';


            foreach ($dump as $_store => $_items) {
                echo "\nSTORE : " . $_store;
                $feed = new \Mothership\Component\Feed\Feed($_items, getcwd() . '/app/etc/feeds/ladenzeile.yaml');
               // $export = $feed->getAttributeDistribution();
                $export = $feed->process();
                File::writeCsv($_store . '_test.csv', $export);
            };
        };
    }
}
