<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Images;

/**
 * Class MissingCommand.
 *
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 *
 * @link      http://www.mothership.de/
 */
class MissingCommand extends AbstractCommand
{
    protected $description = 'Identify missing images';

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

            $this->writeSection($output, 'Import the settings from the settings.php file');

            $pdo = $this->getConnection();

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

            if (count($this->_files_diff) > 0) {

                $options = array(
                    'Show all files',
                    'Save as csv',
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
                    $this->_outputCsv($this->_files_diff);
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