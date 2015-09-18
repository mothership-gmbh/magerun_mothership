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

namespace Mothership\Images;

use N98\Magento\Command\Database\AbstractDatabaseCommand;
use N98\Util\Console\Helper\DatabaseHelper;
use N98\Util\OperatingSystem;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Finder\Finder;

/**
 * Class MissingCommand
 *
 * @category  Mothership
 * @package   Mothership_ResizeCommand
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2015 Mothership GmbH
 * @link      http://www.mothership.de/
 */
class ResizeCommand extends AbstractCommand
{
    /**
     * Directory, where the resized images are
     *
     * @var string
     */
    protected $_resized_path = 'resized';

    /**
     * Command config
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('mothership:images:resize')
            ->setDescription('Generates small images')
            ->addOption(
            'dir',
            null,
            InputOption::VALUE_REQUIRED,
            'The name of the environment');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
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

            /**
             *
             */
            if ($input->getOption('dir')) {
                $this->_resized_path = $input->getOption('env');
            }

            $pdo   = $this->_getDatabaseConnection();
            $query = "SELECT
                        cpemg.value_id AS mg_v,
                        cpemg.position,
                        cpemg.disabled,
                        cpem.entity_id,
                        cpem.value FROM catalog_product_entity_media_gallery_value cpemg
                            LEFT JOIN catalog_product_entity_media_gallery cpem ON cpemg.value_id = cpem.value_id
                            WHERE cpemg.position=0";
            $sth = $pdo->prepare($query);
            $sth->execute();
            $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $_row) {
                $this->resizeImg($_row['value'], 250);
            }
        };
    }

    /**
     * Resize the images
     *
     * @param      $fileName
     * @param      $width
     * @param null $height
     *
     * @return string
     */
    public function resizeImg($fileName, $width, $height = null)
    {
        echo "Resize: " . $fileName;

        $catalog_url = \Mage::getBaseDir(\Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . 'catalog/product';

        $basePath = $catalog_url . $fileName;
        $newPath  = $catalog_url . DS . 'resized' . $fileName;

        $url = \Mage::getBaseUrl('media') . 'catalog/product' . $fileName;

        // if the directory does not exist, create it
        mkdir($catalog_url, 0777, true);

        //if width empty then return original size image's URL
        if ($width != '') {
            //if image has already resized then just return URL
            if (file_exists($basePath) && is_file($basePath) && !file_exists($newPath)) {
                $imageObj = new \Varien_Image($basePath);
                $imageObj->constrainOnly(true);
                $imageObj->keepAspectRatio(false);
                $imageObj->keepFrame(false);
                $imageObj->resize(250);
                $imageObj->save($newPath);
            }
            $url = \Mage::getBaseUrl('media') . 'catalog/product/resized' . $fileName;
        }

        return $url;
    }

    /**
     * Retreive a database connection
     *
     * @return PDO
     */
    protected function _getDatabaseConnection()
    {
        return $this->getHelper('database')->getConnection();
    }
}