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
namespace Mothership_Addons\Lib;

class Logger
{
    /**
     * Key-Value Store for loggin
     *
     * @var mixed
     */
    public static $data;

    static private $instance = null;

    static public function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Logger to count values
     *
     * @param string $key
     *
     * @return void
     */
    public static function logCounter($key)
    {
        if (!array_key_exists($key, Logger::$data)) {
            Logger::$data[$key] = 1;
        } else {
            Logger::$data[$key]++;
        }
    }

    /**
     * Logger for key-value
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public static function logValue($key, $value)
    {
        Logger::$data[$key] = $value;
    }

    /**
     * Get the number of excluded elements
     *
     * @param object object reference to the magerun environment
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    public function getData($object, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $table = array();
        foreach (Logger::$data as $key => $value) {
            $table[] = array('key' => $key, 'value' => $value);
        }

        $object->getHelper('table')->write($output, $table);
    }
}
