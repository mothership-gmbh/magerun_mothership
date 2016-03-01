<?php

namespace Mothership\Magerun\Base\Command\Environment;

use Mothership\Magerun\Base\Command\PHPUnit\TestCase;
use Symfony\Component\Console\Tester\CommandTester;


class EnvironmentsTest extends TestCase
{

    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DumpCommand());
        $command = $this->getApplication()->find('mothership:base:environment:dump');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertContains('Cache config cleaned', $commandTester->getDisplay());
    }
}