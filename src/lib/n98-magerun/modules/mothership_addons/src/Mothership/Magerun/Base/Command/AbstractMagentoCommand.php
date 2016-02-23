<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractMagentoCommand.
 *
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 *
 * @link      http://www.mothership.de/
 */
abstract class AbstractMagentoCommand extends \N98\Magento\Command\Database\AbstractDatabaseCommand
{
    /**
     * Replace the generic description by your own one.
     *
     * @var string description
     */
    protected $description = '<error>GENERIC DESCRIPTION. REPLACE IT</error>';

    /**
     * Generic command name creator. If you follow the Namespace convention, then
     * the commands will be created dynamically.
     *
     * \Mothership\Magerun\Modulename\...\Class
     */
    protected function configure()
    {
        $classExploded = explode('\\', get_called_class());

        $command = [];
        foreach ($classExploded as $part) {
            $part = strtolower($part);

            if ('magerun' != $part && 'command' != $part) {
                if (strpos($part, 'command') !== false) {
                    $part = str_replace('command', '', $part);
                }
                $command[] = $part;
            }
        }

        $this->setName(implode(':', $command))
            ->setDescription($this->description);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @throws \Exception
     *
     * @return void|int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if (false === $this->initMagento()) {
            throw new \Exception('Initialization of Magento could not be completed.');
        };

        // Add PSR-0/4 support
        \Mage::dispatchEvent('add_spl_autoloader');
    }
}
