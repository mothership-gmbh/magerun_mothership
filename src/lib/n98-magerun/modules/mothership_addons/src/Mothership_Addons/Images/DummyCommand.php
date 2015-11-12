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

namespace Mothership_Addons\Images;

/**
 * Class DummyCommand
 *
 * @category  Mothership
 * @package   Mothership_DummyCommand
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2015 Mothership GmbH
 * @link      http://www.mothership.de/
 *
 *            Creates a media catalog with dummy images.
 */
class DummyCommand extends AbstractCommand
{
    /**
     * Command config
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('mothership:images:create-dummy')
            ->setDescription('For each missing image, create a dummy file')
        ;
    }

    /**
     * Find all missing files
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {

            $this->_parseCatalogProductDirectory();

            $this->writeSection($output, 'Reading all files from the database.');

            $pdo = $this->_getDatabaseConnection();

            $query = "SELECT * FROM catalog_product_entity_media_gallery";
            $sth = $pdo->prepare($query);
            $sth->execute();
            $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $_row) {
                $this->_files_remote[] = $_row['value'];
            }

            // compare value
            foreach ($this->_files_remote as $_file) {
                if (!in_array($_file, $this->_files_local)) {
                    $this->_files_diff[] = $_file;
                }
            }

            $_media_path   = \Mage::getBaseDir('media') . '/catalog/product';
            $_source_image = \Mage::getBaseDir('media') . '/dummy.jpg';


            if (count($this->_files_diff) > 0) {

                $options = array(
                    'Show all files',
                    'Replace with dummy files. Ensure that there is a dummy.jpg in the /media directory',
                );

                $message = 'There are ' . count($this->_files_diff) . ' files missing';

                $dialog = $this->getHelper('dialog');
                $option = $dialog->select(
                    $output,
                    $message,
                    $options
                );

                $output->writeln('<info>You have just selected option: ' . $options[$option] . '</info>');

                if ($option == 1) {

                    foreach ($this->_files_diff as $_file) {
                        $_image_file = \Mage::getBaseDir('media') . '/catalog/product' . $_file;
                        $path = pathinfo($_image_file);
                        if (!file_exists($path['dirname'])) {
                            mkdir($path['dirname'], 0777, true);
                        }
                        if (!copy($_source_image,$_image_file)) {
                            echo "copy failed \n";
                        }
                    }


                } else {

                    foreach ($this->_files_diff as $_file) {
                        $table[] = array (
                            'file'     => $_file,
                        );
                    }

                    $this->getHelper('table')->write($output, $table);
                }
            } else {
                $output->writeln('<info>There are no files missing</info>');
            }
        };
    }
}