<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Command\Workflow;

use Mothership\Magerun\Base\Command\PHPUnit\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class Mothership\Magerun\Base\Command\Workflow\RunCommandTest.
 *
 * @category  Mothership
 *
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 *
 * @link      http://www.mothership.de/
 */
class RunCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('mothership:base:workflow:run');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--config' => 'Example.yaml']);

        $this->assertContains('Option "Example.yaml" set', $commandTester->getDisplay());
    }
}
