<?php

namespace App\Tests\Command\Cloudflare;

use App\Command\Cloudflare\ZonesListCommand;
use Symfony\Component\Console\Command\Command;

class ZonesListCommandTest extends CloudflareCommandTestCase
{
    public function getCommandName(): string
    {
        return ZonesListCommand::$defaultName;
    }

    /**
     * @dataProvider successCommandDataProvider
     */
    public function testSuccessCommand(array $availableZones): void
    {
        $this->client->setZones($availableZones);

        $commandTester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $output = $commandTester->getDisplay();

        if (empty($availableZones)) {
            self::assertStringContainsString('No zone found', $output);

            return;
        }

        self::assertStringContainsString(sprintf('%d zone(s):', count($availableZones)), $output);

        foreach ($availableZones as $availableZone) {
            self::assertMatchesRegularExpression(
                sprintf(
                    '/%s\s+%s\s+%s/',
                    preg_quote($availableZone['name']),
                    preg_quote($availableZone['id']),
                    preg_quote($availableZone['status'])
                ),
                $output
            );
        }
    }

    public function successCommandDataProvider(): iterable
    {
        yield [[]];
        yield [[
            ['id' => 'abc123', 'name' => 'foo.test', 'status' => 'active'],
        ]];
        yield [[
            ['id' => 'abc123', 'name' => 'foo.test', 'status' => 'active'],
            ['id' => 'def456', 'name' => 'bar.test', 'status' => 'active'],
        ]];
    }
}
