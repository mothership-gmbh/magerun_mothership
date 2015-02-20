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

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\OperatingSystem;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Finder\Finder;
use Mothership\Lib\Database;
use Mothership\Lib\File;
use Mothership\Lib\Logger;

/**
 * Class AbstractCommand
 *
 * @category  Mothership
 * @package   Mothership_AbstractCommand
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2015 Mothership GmbH
 * @link      http://www.mothership.de/
 */
class AbstractCommand extends Database
{
    /**
     * The excluded paths are all paths in the table core_config_data
     * which are ignored by the dump. You should set them in the /resource/config.php
     *
     * @var array
     */
    protected $_files_local;

    /**
     * @var
     */
    protected $_files_remote;

    /**
     * @var
     */
    protected $_files_diff;

    /**
     * @param $data
     */
    protected function _outputCsv($data)
    {
        $header = array('filename');
        File::writeCsv('test.csv', $header, $data);
    }

    /**
     * Grab all files in the directory and save them
     *
     * @return void
     */
    protected function _parseCatalogProductDirectory()
    {
        $this->_files_local = array ();
        $dir                = new \RecursiveDirectoryIterator(\Mage::getBaseDir('media') . '/catalog/product');
        $objects            = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($objects as $name => $object) {
            if ($object->isFile()) {

                $_tmp_filename     = $object->getPathname();
                $_tmp_filename_arr = explode('catalog/product', $_tmp_filename);

                if (preg_match('@^\/configurator\/@', $_tmp_filename_arr[1])) {
                    continue;
                }

                if (false == stristr($_tmp_filename_arr[1], '/cache/')) {
                    $this->_files_local[] = $_tmp_filename_arr[1];
                }
            }
        }
    }
}