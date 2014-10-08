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

/**
 * Configuration File for the Dump-Command. Create a new file config.php which will be used
 * by the dump-command
 */

return array(

    'dump' => array(
        /**
         * Excluded paths from the core_config_data table.
         * You can use regex to exclude the paths as the script will use preg_match
         */
        'excluded_paths' => array(
            '/^carriers.*/',
            '/^google.*/',
            '/^sales.*/',
            '/^sales_pdf.*/',
            '/^sales_email.*/',
            '/^catalog.*/',
            '/^newsletter.*/',
            '/^payment.*/',
            '/^design.*/',
            '/^tax.*/',
        )
    ),

    'import' => array(

    )
);