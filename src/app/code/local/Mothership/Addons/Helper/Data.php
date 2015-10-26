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
 * Class Mothership_Addons_Helper_Data
 *
 * @category  Mothership
 * @package   Mothership_Addons
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2015 Mothership GmbH
 * @link      http://www.mothership.de/getStoreIndexSettings
 */
class Mothership_Addons_Helper_Data extends Mage_Core_Helper_Abstract
{
    const CONFIG_KEY_EXTENSION_ENABLED = 'example/options/mothership_elasticsearch_extension_enabled';
    const CONFIG_KEY_SERVER            = 'catalog/search/elasticsearch_servers';

    /**
     * Determine if a specific store is allowed to index/reindex.
     */
    const CONFIG_KEY_INDEX_ALLOWED     = 'catalog/search/elasticsearch_index_allowed';

    /**
     * @var array
     */
    protected $_config;

    /**
     * Handles message
     *
     * @param bool $lnbr should a linebreak be applied
     *
     * @return $this
     */
    public function handleMessage()
    {
        $args = func_get_args();
        $msg  = array_shift($args);
        $msg  = @vsprintf($msg, $args);
        if (php_sapi_name() == 'cli') {
            echo @vsprintf($msg, $args);
        } else {
            Mage::log($msg, Zend_Log::DEBUG, 'mothership_addons.log', true);
        }
        return $this;
    }

    /**
     * Handles error
     *
     * @param string $error
     *
     * @return $this
     */
    public function handleError($error)
    {
        if (!Mage::app()->getRequest()->isAjax()) {
            if (Mage::app()->getStore()->isAdmin()) {
                Mage::getSingleton('adminhtml/session')->addError($error);
            } elseif ($this->isDebugEnabled()) {
                echo Mage::app()->getLayout()
                    ->createBlock('core/messages')
                    ->addError($error)
                    ->getGroupedHtml();
            }
        }

        Mage::log($error, Zend_Log::CRIT, 'elasticsearch.log', true);

        return $this;
    }

    /**
     * Get the resource model
     *
     * @return Mage_Core_Model_Resource
     */
    public function getResource()
    {
        return Mage::getSingleton('core/resource');
    }

    /**
     * Get the PDO Adapter
     *
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    public function getAdapter()
    {
        return $this->getResource()->getConnection('read');
    }
}