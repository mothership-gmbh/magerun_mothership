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
 * @category  Mothership_Magerun
 * @package   Mothership_Magerun_Addons
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2015 Mothership GmbH
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.mothership.de/
 */
namespace Mothership_Addons\Feed;

use Mothership\Component\Feed\FactoryFeedAbstract;

/**
 * Class FeedFactory
 *
 * @category  Mothership_Magerun
 * @package   Mothership_Magerun_Addons
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2015 Mothership GmbH
 * @link      http://www.mothership.de/
 *
 *            Extend the FactoryFeedAbstract as we use dynamic database configuration
 */
class FeedFactory extends FactoryFeedAbstract
{
    /**
     * @param string $mapping_path
     * @param mixed  $extended_configuration
     *
     * @throws \Mothership\Component\Feed\Exception\FactoryFeedException
     */
    public function __construct($mapping_path, $extended_configuration)
    {
        parent::__construct($mapping_path);

        /**
         * Overwrite the default database configuration as the database configuration
         * are always dynamic and should not be set in the configuration file
         */
        $this->mapping['db'] = $extended_configuration['db'];
    }
}