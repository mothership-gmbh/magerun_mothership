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
 * Class AbstractCommand.
 *
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 *
 * @link      http://www.mothership.de/
 */
class CleanCommand extends AbstractCommand
{
    protected $description = 'Remove unused images from the catalog/product directory';

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

            $this->_parseCatalogProductDirectory();

            $this->writeSection($output, 'Import the settings from the settings.php file');

            $pdo = $this->getConnection();

            $query = "SELECT * FROM catalog_product_entity_media_gallery";
            $sth   = $pdo->prepare($query);
            $sth->execute();
            $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $_row) {
                $this->_files_remote[] = $_row['value'];
            }

            // compare value
            foreach ($this->_files_local as $_file) {
                if (!in_array($_file, $this->_files_remote)) {
                    $this->_files_diff[] = $_file;
                }
            }

            if (count($this->_files_diff) > 0) {

                $options = array (
                    'Show all files',
                    'Save as csv',
                    'Delete all files',
                );


                $message = 'There are ' . count($this->_files_diff) . ' files, which can be deleted.';

                $dialog = $this->getHelper('dialog');
                $option = $dialog->select(
                    $output,
                    $message,
                    $options
                );

                $output->writeln('<info>You have just selected option: ' . $options[$option] . '</info>');

                if ($option == 1) {
                    $this->_outputCsv($this->_files_diff);
                } elseif ($option == 2) {
                    // delete files
                    foreach ($this->_files_diff as $_file) {
                        $_image_file = \Mage::getBaseDir('media') . '/catalog/product' . $_file;
                        $output->writeln('<comment>Remove: ' . $_image_file . '</comment>');
                        unlink($_image_file);
                    }
                } else {

                    $sizes = 0;
                    foreach ($this->_files_diff as $_file) {
                        $size = filesize(\Mage::getBaseDir('media') . '/catalog/product/' .$_file);
                        $sizes += $size;
                        $table[] = array (
                            'file' => $_file,
                            'size' => $size
                        );
                    }

                    $this->getHelper('table')->write($output, $table);
                    $output->writeln('<info>Total size in kb: ' . $sizes / 1024 . ' (' . $sizes / 1024 /1024 . ' MB)</info>');

                }
            } else {
                $output->writeln('<info>There are no files missing</info>');
            }
    }
}