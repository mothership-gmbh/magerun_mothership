<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Images;

use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ResizeCommand.
 *
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 *
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

    protected $description = 'Resize the images';

    /**
     * Command config
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();
        $this->addOption(
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
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

                try {
                    $imageObj = new \Varien_Image($basePath);
                    $imageObj->constrainOnly(true);
                    $imageObj->keepAspectRatio(false);
                    $imageObj->keepFrame(false);
                    $imageObj->resize($width);
                    $imageObj->save($newPath);
                } catch (\Exception $e) {
                    echo $e->getMessage();
                }

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