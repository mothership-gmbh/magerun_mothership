<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Fixtures;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Yaml\Dumper;

use \Mothership\Magerun\Base\Command\AbstractMagentoCommand;

/**
 * Class ProductCommand.
 *
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 *
 * @link      http://www.mothership.de/
 */
class ProductCommand extends AbstractMagentoCommand
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

    protected $description = 'Create a YAML fixture for EcomDev_PHPUnit in the current working directory.';

    /**
     * You can pass a env-option like --entity_id=123
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();
        $this->addOption(
            'entity_id',
            null,
            InputOption::VALUE_REQUIRED,
            'The product id'
        );
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