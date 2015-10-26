<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Mothership GmbH
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 * Class Mothership_Addons_Helper_Product
 *
 * @category  Mothership
 * @package   Mothership_Addons
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2015 Mothership GmbH
 * @link      http://www.mothership.de/
 */
class Mothership_Addons_Helper_Product extends Mage_Core_Helper_Abstract
{
    /**
     * @var Mothership_Elasticsearch_Helper_Data
     */
    protected $_helper_data;


    protected $_attribute_options = [];

    /**
     * The current store id is set in an iteration
     *
     * @var int
     */
    protected $_current_store_id;

    /**
     * Container for all parsed data. Will be enriched while running the export
     *
     * @var mixed
     */
    protected $_data = [];

    /**
     * The product helper depends on the data helper, which contains useful
     * methods
     */
    public function __construct()
    {
        $this->_helper_data       = Mage::helper('mothership_addons/data');
    }

    /**
     * @param array $filters
     * @param int   $split
     *
     * @return array
     */
    public function export($filters = array (), $split = 2000)
    {
        $product           = Mage::getModel('catalog/product');
        $attributesByTable = $product->getResource()->loadAllAttributes($product)->getAttributesByTable();
        $mainTable         = $product->getResource()->getTable('catalog_product_entity');

        foreach (Mage::app()->getStores() as $store) {
            /** @var $store Mage_Core_Model_Store */
            if (!$store->getIsActive()) {
                continue;
            }

            $this->_current_store_id               = (int) $store->getId();
            $this->_data[$this->_current_store_id] = [];

            if (isset($filters['store_id'])) {
                if (!is_array($filters['store_id'])) {
                    $filters['store_id'] = array ($filters['store_id']);
                }
                if (!in_array($this->_current_store_id, $filters['store_id'])) {
                    continue;
                }
            }

            /**
             * SELECT `e`.*,
             *      `at_status`.`value`     AS `status`,
             *      `at_visibility`.`value` AS `visibility`
             *  FROM   `catalog_product_entity` AS `e`
             *      INNER JOIN `catalog_product_entity_int` AS `at_status`
             *          ON ( `at_status`.`entity_id` = `e`.`entity_id` )
             *          AND ( `at_status`.`attribute_id` = '96' )
             *          AND ( `at_status`.`store_id` = 0 )
             *      INNER JOIN `catalog_product_entity_int` AS `at_visibility`
             *          ON ( `at_visibility`.`entity_id` = `e`.`entity_id` )
             *          AND ( `at_visibility`.`attribute_id` = '102' )
             *          AND ( `at_visibility`.`store_id` = 0 )
             *      WHERE  ( at_status.value = 1 )
             *          AND ( at_visibility.value = '4' )
             */
            $product_collection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
                ->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
                ->setPageSize(500);

            if (array_key_exists('entity_id', $filters) && !empty($filters['entity_id'])) {
                $product_collection->addAttributeToFilter('entity_id', array('in' => $filters['entity_id']));
            }

            $configurable = [];
            $entities     = [];

            //for($i=1; $i<= 1; $i++)
            for($i=1; $i<= $product_collection->getLastPageNumber(); $i++)
            {
                $product_collection->setCurPage($i);
                $product_collection->load();

                foreach($product_collection as $_item)
                {
                    // only get items which do not have a parent
                    //if (empty(Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($_item->getId()))) {

                    $entities[] = $_item->getId();
                    $_data = $_item->getData();
                    unset($_data['stock_item']);

                    /**
                     * This will just set the most basic example.
                     *
                     */
                    $this->_data[$this->_current_store_id][$_item->getId()] = $_data;

                    if ($_item->getTypeId() == 'configurable') {
                        $configurable[] = $_item;
                    }
                }
                $product_collection->clear();
            }

            $this->_addParentChildRelation($entities);
            $this->_addConfigurableOptions($configurable);
            $this->_addAttributes($attributesByTable, $mainTable, $entities);
            $this->_addCategoryData($entities, $store);
            $this->_addStockQty($entities);


        }
        return $this->_data;
    }

    /**
     * Add the parent-children relation based on the table catalog_product_relation. This might
     * be required to be able to group all products. In the traditional way of parent-children relations,
     * you will not be able to get the parent item but only the children item. By executing
     * two similar queries, but one for children and one for parents we will set the parent_id
     * for both entities, simples and configurables.
     *
     * @param mixed $entity_ids
     */
    protected function _addParentChildRelation($entity_ids)
    {
        $select = $this->_helper_data->getAdapter()
            ->select()
            ->from(array ('cpr' => $this->_helper_data->getResource()->getTableName('catalog/product_relation')), array ('parent_id', 'child_id'))
            ->where('cpr.child_id IN (?)', $entity_ids);

        $q = $this->_helper_data->getAdapter()->query($select);

        while ($row = $q->fetch()) {
            $this->_addToProduct($row['child_id'], [
                    'parent_id'  => $row['parent_id']
                ]
            );
        }

        /**
         * Try to map the parent_id to the parent_item. In theory this call is not required
         * as an item which is 'configurable' is always its own parent. But we just do this call
         * to be super secure.
         */
        $select = $this->_helper_data->getAdapter()
            ->select()
            ->from(array ('cpr' => $this->_helper_data->getResource()->getTableName('catalog/product_relation')), array ('parent_id', 'child_id'))
            ->where('cpr.parent_id IN (?)', $entity_ids);

        $q = $this->_helper_data->getAdapter()->query($select);

        while ($row = $q->fetch()) {
            $this->_addToProduct($row['parent_id'], [
                    'parent_id' => $row['parent_id']
                ]
            );
        }
    }

    /**
     * Update the products array with the stock data by doing a query to the cataloginventory_stock_status
     * table. There are also attributes like website_id and stock which are currently not mandatory.
     *
     * @param array $entity_ids
     *
     * @return void
     */
    protected function _addStockQty($entity_ids)
    {
        $select = $this->_helper_data->getAdapter()
            ->select()
            ->from(array ('css' => $this->_helper_data->getResource()->getTableName('cataloginventory/stock_status')), array ('product_id', 'qty', 'stock_status'))
            ->where('css.product_id IN (?)', $entity_ids);

        $q = $this->_helper_data->getAdapter()->query($select);

        while ($row = $q->fetch()) {
            $this->_addToProduct($row['product_id'], [
                'qty' => $row['qty'],
                'stock_status' => $row['stock_status']
                ]
            );
        }
    }

    /**
     * @param mixed $products
     *
     * @return void
     */
    protected function _addConfigurableOptions($products)
    {
        foreach ($products as $_product) {
            $this->_helper_data->handleMessage('.');
            $ids = Mage::getResourceSingleton('catalog/product_type_configurable')
                ->getChildrenIds($_product->getId());

            if (empty($ids[0])) {
                continue;
            }

            $configurableAttributeCollection = $_product->getTypeInstance()->getConfigurableAttributes();

            foreach ($configurableAttributeCollection as $_attribute) {
                $attrCode = $_attribute->getProductAttribute()->getAttributeCode();

                $_attribute_options = $this->_getAttributeOptions($attrCode);

                $_subproducts = Mage::getModel('catalog/product')->getCollection()
                    ->addIdFilter($ids)
                    ->addAttributeToSelect($attrCode)->groupByAttribute($attrCode);

                foreach ($_subproducts as $_sp) {
                    $_attribute_option_label = $_attribute_options[$_sp->getData($attrCode)];

                    if (null !== $_attribute_option_label) {

                        $this->_data[$this->_current_store_id][$_product->getId()]['option_' . $attrCode][] = $_attribute_option_label;
                    }
                }
            }
        }
    }

    /**
     * Calculate the total stock for a configurable item
     */
    protected function _getConfigurableTotalStock()
    {
        foreach ($configurable as $_configurable) {

            $conf = Mage::getModel('catalog/product_type_configurable')->setProduct($_configurable);
            $simple_collection = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
            foreach($simple_collection as $simple_product){
                $result[$store_id][$_configurable->getId()]['stock'] += (int) Mage::getModel('cataloginventory/stock_item')->loadByProduct($simple_product)->getQty();
            }
        }
    }

    /**
     * Much faster helper method to grab all product attributes.
     *
     * @param mixed                        $attributesByTable
     * @param string                       $mainTable
     * @param mixed                        $entity_ids
     *
     * @return array
     */
    protected function _addAttributes($attributesByTable, $mainTable, $entity_ids)
    {
        $adapter = $this->_helper_data->getAdapter();
        $this->_helper_data->handleMessage("> get Attributes");
        $products = array();
        foreach ($attributesByTable as $table => $allAttributes) {
            $allAttributes = array_chunk($allAttributes, 25);
            foreach ($allAttributes as $attributes) {
                $select = $adapter->select()
                    ->from(array ('e' => $mainTable), array ('id' => 'entity_id', 'sku'));

                foreach ($attributes as $attribute) {

                    $attributeId   = $attribute->getAttributeId();
                    $attributeCode = $attribute->getAttributeCode();

                    if (!isset($attrOptionLabels[$attributeCode])
                        && $this->isAttributeUsingOptions(
                            $attribute
                        )
                    ) {
                        $options = $attribute->setStoreId($this->_current_store_id)
                                             ->getSource()
                                             ->getAllOptions();
                        foreach ($options as $option) {
                            if (!$option['value']) {
                                continue;
                            }
                            $attrOptionLabels[$attributeCode][$option['value']] = $option['label'];
                        }
                    }
                    $alias1 = $attributeCode . '_default';
                    $select->joinLeft(
                        array ($alias1 => $adapter->getTableName($table)),
                        "$alias1.attribute_id = $attributeId AND $alias1.entity_id = e.entity_id AND $alias1.store_id = 0",
                        array ()
                    );
                    $alias2    = $attributeCode . '_store';
                    $valueExpr = $adapter->getCheckSql(
                        "$alias2.value IS NULL",
                        "$alias1.value",
                        "$alias2.value"
                    );
                    $select->joinLeft(
                        array ($alias2 => $adapter->getTableName($table)),
                        "$alias2.attribute_id = $attributeId AND $alias2.entity_id = e.entity_id AND $alias2.store_id = {$this->_current_store_id}",
                        array ($attributeCode => $valueExpr)
                    );
                }

                $select->where('e.entity_id IN (?)', $entity_ids);
                $query = $adapter->query($select);

                while ($row = $query->fetch()) {
                    $row       = array_filter($row, 'strlen');
                    $row['id'] = (int) $row['id'];
                    $productId = $row['id'];
                    if (!isset($products[$productId])) {
                        $products[$productId] = array ();
                    }
                    foreach ($row as $code => &$value) {
                        if (isset($attributesByTable[$table][$code])) {
                            $value = $this->_formatValue($attributesByTable[$table][$code], $value);
                        }
                        if (isset($attrOptionLabels[$code])) {
                            if (is_array($value)) {
                                $label = array ();
                                foreach ($value as $val) {
                                    if (isset($attrOptionLabels[$code][$val])) {
                                        $label[] = $attrOptionLabels[$code][$val];
                                    }
                                }
                                if (!empty($label)) {
                                    $row[$code] = $label;
                                }
                            } elseif (isset($attrOptionLabels[$code][$value])) {
                                $row[$code] = $attrOptionLabels[$code][$value];
                            }
                        }
                    }
                    unset($value);

                    $this->_addToProduct($productId, $row);
                }
            }
        }
    }

    /**
     * Lazy loader for attribute options
     *
     * @param string $code
     *
     * @return mixed
     */
    protected function _getAttributeOptions($code)
    {
        if (array_key_exists($code, $this->_attribute_options)) {
            return $this->_attribute_options[$code];
        }

        $this->_attribute_options[$code] = [];
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $code);
        foreach ($attribute->getSource()->getAllOptions(true, true) as $_option) {
            $this->_attribute_options[$code][$_option['value']] = $_option['label'];
        }
        return $this->_attribute_options[$code];
    }

    /**
     * Retrieve store category names mapping
     *
     * @param \Mage_Core_Model_Store $store
     *
     * @return array
     */
    public function getCategoryNames()
    {
        $adapter = $this->_helper_data->getAdapter();

        $attributeId = Mage::getSingleton('eav/entity_attribute')
            ->getIdByCode(Mage_Catalog_Model_Category::ENTITY, 'name');
        $select = $adapter->select()
            ->from($this->_helper_data->getResource()->getTableName('catalog_category_entity_varchar'), array('entity_id', 'value'))
            ->where('attribute_id = ?', $attributeId) // only category name attribute values
            ->where('store_id IN (?)', array(0, $this->_current_store_id)) // use default value if not overriden in store view scope
            ->order(array('entity_id ASC', 'store_id ASC')); // used to handle store view overrides

        return $adapter->fetchPairs($select);
    }

    /**
     * Retreive the category data
     *
     * @param mixed $data The origin which should be enriched with the category data
     *
     * @return mixed
     */
    protected function _addCategoryData($entity_ids, $store)
    {

        $categoryNames = $this->getCategoryNames();

        /**
         * Grab all category ids first
         */
        $select = $this->_helper_data->getAdapter()->select()
                       ->from(
                           array(
                               $this->_helper_data->getResource()->getTableName('catalog_category_entity')
                           ),
                           array('entity_id', 'path')
                       );
        $query = $this->_helper_data->getAdapter()->query($select);
        $rows = $query->fetchAll();

        $categories = [];
        /**
         *
         */
        foreach($rows as $_row) {
            $categories[$_row['entity_id']] = $_row['path'];
        }

        /**
         * Grab all category ids first
         */
        $select = $this->_helper_data->getAdapter()->select()
            ->from(
                array(
                    $this->_helper_data->getResource()->getTableName('catalog_category_product_index')
                ),
                array('category_id', 'position', 'product_id')
            )
            ->where('product_id IN (?)', $entity_ids)
            ->where('store_id = ?', $this->_current_store_id)
            ->where('category_id > 1') // ignore global root category
            ->where('category_id != ?', $store->getRootCategoryId()); // ignore store root category


        $query = $this->_helper_data->getAdapter()->query($select);
        $rows = $query->fetchAll();

        $products = [];



        /**
         *
         */
        foreach($rows as $_row) {
            $products[$_row['product_id']][$_row['category_id']] = $_row['position'];

            $path_exploded = explode('/', $categories[$_row['category_id']]);
            $path_exploded_tmp = [];
            foreach ($path_exploded as $_path) {
                $path_exploded_tmp[] = $categoryNames[$_path];
            }

            $_data = array(
                'id'        => (int) $_row['category_id'],
                'root_id'   => $store->getRootCategoryId(),
                'path'      => $categories[$_row['category_id']],
                'path_name' => implode('/', $path_exploded_tmp),
                'pos'       => (int) $_row['position'],
                'name'      => $categoryNames[$_row['category_id']]
            );

            $this->_data[$this->_current_store_id][$_row['product_id']]['catalog_category'][] = $_data;
        }
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isIndexOutOfStockProducts($store = null)
    {
        return Mage::getStoreConfigFlag(Mage_CatalogInventory_Helper_Data::XML_PATH_SHOW_OUT_OF_STOCK, $store);
    }

    /**
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @return bool
     */
    public function isAttributeIndexable($attribute)
    {
        return ($attribute->getIsSearchable() || $attribute->getIsVisibleInAdvancedSearch())
        && !in_array($attribute->getAttributeCode(), array('status', 'tax_class_id', 'price'));
    }

    /**
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     *
     * @return bool
     */
    public function isAttributeUsingOptions($attribute)
    {
        $model = Mage::getModel($attribute->getSourceModel());
        $backend = $attribute->getBackendType();

        return $attribute->usesSource() &&
        ($backend == 'int' && $model instanceof Mage_Eav_Model_Entity_Attribute_Source_Table) ||
        ($backend == 'varchar' && $attribute->getFrontendInput() == 'multiselect');
    }

    /**
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @param string $value
     * @return mixed
     */
    protected function _formatValue($attribute, $value)
    {
        if ($attribute->getBackendType() == 'decimal') {
            if (strpos($value, ',')) {
                $value = array_unique(array_map('floatval', explode(',', $value)));
            } else {
                $value = (float) $value;
            }
        } elseif ($attribute->getSourceModel() == 'eav/entity_attribute_source_boolean'
            || $attribute->getFrontendInput() == 'boolean')
        {
            $value = (bool) $value;
        } elseif ($attribute->usesSource() || $attribute->getFrontendClass() == 'validate-digits') {
            if (strpos($value, ',')) {
                $value = array_unique(array_map('intval', explode(',', $value)));
            } else {
                $value = (int) $value;
            }
        }

        return $value;
    }

    /**
     * Wrapper for adding product data
     *
     * @param int   $product_id The primary key of the product
     * @param mixed $data       Any valid key-value combination
     */
    protected function _addToProduct($product_id, array $data)
    {
        $this->_data[$this->_current_store_id][$product_id] = array_merge($this->_data[$this->_current_store_id][$product_id], $data);
    }
}