<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestCase extends KernelTestCase
{
    protected function executeCommand(array $inputArgs = []): CommandTester
    {
        $application = new Application(self::$kernel);

        $command = $application->get($this->getCommandName());

        $commandTester = new CommandTester($command);
        $commandTester->execute($inputArgs, ['interactive' => false]);

        return $commandTester;
    }

    abstract protected function getCommandName(): string;
}
