<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Queue;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Mothership\Magerun\Base\Command\AbstractMagentoCommand;

/**
 * Class CleanCommand.
 *
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 *
 * @link      http://www.mothership.de/
 */
class CleanCommand extends AbstractMagentoCommand
{
    protected $description = 'Clean the PHP-Queue';

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        \Resque::setBackend(\Mage::getStoreConfig('mothership_intex/queue/host'));
        \Resque::dequeue(\Mage::getStoreConfig('mothership_intex/queue/name'));
    }
}
