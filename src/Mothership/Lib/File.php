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
 * @copyright 2014 Mothership GmbH
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.mothership.de/
 */
namespace Mothership\Lib;

class File
{
    /**
     * Will load an php configuration file. The configuration file should be an php array which will be returned.
     * For example <?php return array();
     *
     * @throws \Exception
     */
    public static function loadConfig($file_name)
    {
        if (!file_exists($file_name)) {
            throw new \Exception('Missing Configuration file: ' . $file_name);
        }
        return include_once $file_name;
    }

    /**
     * Write a file
     *
     * @param string $filename
     * @param string $data
     */
    public static function write($filename, $data)
    {
        $fh = fopen($filename, 'w');
        fputs($fh, $data);
        fclose($fh);
    }

    /**
     * Write a php array
     *
     * @param string $filename
     * @param string $data
     */
    public static function writePHPArray($filename, $data)
    {
        $fh = fopen($filename, 'w');
        fputs($fh, "<?php \n return ");
        fputs($fh, var_export($data, true));
        fputs($fh, ";");
        fclose($fh);
    }
}
