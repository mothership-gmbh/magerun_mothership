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
 * @package   Mothership_Fixtures
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2015 Mothership GmbH
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.mothership.de/
 */

namespace Mothership_Addons\Fixtures;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\OperatingSystem;
use phpDocumentor\Transformer\Exception;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Finder\Finder;

use Symfony\Component\Yaml\Dumper;

use Mothership_Addons\Lib\Database;
use Mothership_Addons\Lib\File;

class ProductCommand extends Database
{
    /**
     * @var int
     */
    protected $_storeId = null;

    /**
     * @var string
     */
    protected $_domain  = null;

    /**
     * This is used as model prefix as well
     *
     * @var string
     */
    protected $_prefix = 'catalog/product';

    /**
     * FIFO-Stack for the products
     *
     * @var array
     */
    protected $_stack = array(
        'eav' => array(
            'catalog_product' => array()
        )
    );

    /**
     * You can pass a env-option like --entity_id=123
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('mothership:fixtures:product')
            ->setDescription('Create a YAML fixture for EcomDev_PHPUnit in the current working directory.')
            ->addOption(
                'entity_id',
                null,
                InputOption::VALUE_REQUIRED,
                'The product id'
            )
        ;
    }

    /**
     * Handy function to get the depth of an array
     *
     * @see http://stackoverflow.com/questions/262891/is-there-a-way-to-find-out-how-deep-a-php-array-is
     *
     * @param array $array
     *
     * @return int
     */
    public static function array_depth(array $array) {
        $max_depth = 1;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = self::array_depth($value) + 1;

                if ($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }

        return $max_depth;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
            $this->_base_path = __DIR__ . '/resource';


            $_product_ids = $input->getOption('entity_id');
            if (null === $_product_ids) {
                $output->writeln('<error>No entity_ids given</error>');
                return;
            }

            $_product_ids_exploded = explode(',', $_product_ids);

            foreach ($_product_ids_exploded as $_product_id) {
                $_model = \Mage::getModel('catalog/product')->load($_product_id);

                try {
                    $this->_parse($_model, $_product_id);
                } catch (Exception $e) {
                    $output->writeln('<error>' . $e->getMessage() . '</error>');
                }
            }



            $dumper = new Dumper();
            $yaml =  $dumper->dump($this->_stack, self::array_depth($this->_stack));

            echo $yaml;

            file_put_contents(getcwd() . '/eav-1.yaml', $yaml);
        }
    }

    /**
     * @param $model
     *
     * @throws Exception
     */
    protected function _parse($model, $entity_id)
    {
        if (null === $model->getId()) {
            throw new Exception('Model with ID: ' . $entity_id . 'does not exists.');
        }

        $mandatory_params = array(
            'entity_id',
            'type_id',
            'attribute_set_id',
            'sku',
            'name',
            'short_description',
            'description',
            'url_key',
            'book',
            'stock',
            'qty',
            'is_in_stock',
            'website_ids',
            'category_ids',
            'price',
            'tax_class_id',
            'status',
            'visibility',
        );

        foreach ($model->getData() as $_k => $_v) {
            if (in_array($_k, $mandatory_params)) {
                $_model_data[$_k] = $_v;
            }
        }

        $this->_stack['eav']['catalog_product'][] = $_model_data;

    }
}