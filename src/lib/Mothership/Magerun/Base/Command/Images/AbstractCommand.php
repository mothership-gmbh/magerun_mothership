<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Images;

use \Mothership\Magerun\Base\Command\AbstractMagentoCommand;

/**
 * Class AbstractCommand.
 *
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 *
 * @link      http://www.mothership.de/
 */
class AbstractCommand extends AbstractMagentoCommand
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