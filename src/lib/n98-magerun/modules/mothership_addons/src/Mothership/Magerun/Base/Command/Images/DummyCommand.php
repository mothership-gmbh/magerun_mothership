<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Images;

use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DummyCommand.
 *
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 *
 * @link      http://www.mothership.de/
 */
class DummyCommand extends AbstractCommand
{
    protected $description = 'For each missing image, create a dummy file';

    /**
     * Find all missing files
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {


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
    }
}