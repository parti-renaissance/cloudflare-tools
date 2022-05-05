<?php

namespace App\Tests\Command\Cloudflare;

use App\Command\Cloudflare\DnsImportCommand;
use Symfony\Component\Console\Command\Command;

class DnsImportCommandTest extends CloudflareCommandTestCase
{
    public function getCommandName(): string
    {
        return DnsImportCommand::$defaultName;
    }

    public function testSuccessCommand(): void
    {
        $this->client->setZones([['id' => 'abc123', 'name' => 'foo.test', 'status' => 'active']]);

        $commandTester = $this->executeCommand([
            'zone' => 'foo.test',
            'file' => 'dns.csv',
        ]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        self::assertStringContainsString('Successfully processed 4 DNS records.', $commandTester->getDisplay());
    }

    /**
     * @dataProvider invalidArgumentsDataProvider
     */
    public function testInvalidArguments(array $arguments, int $expectedStatusCode, string $expectedErrorMessage): void
    {
        $this->client->setZones([['id' => 'abc123', 'name' => 'foo.test', 'status' => 'active']]);

        $commandTester = $this->executeCommand($arguments);

        self::assertSame($expectedStatusCode, $commandTester->getStatusCode());
        self::assertStringContainsString($expectedErrorMessage, $commandTester->getDisplay());
    }

    public function invalidArgumentsDataProvider(): iterable
    {
        yield [
            ['file' => 'dns.csv'],
            Command::INVALID,
            'No zone provided.',
        ];

        yield [
            ['zone' => 'foo.test'],
            Command::INVALID,
            'No file provided.',
        ];

        yield [
            [
                'zone' => 'foo.test',
                'file' => 'foo.csv',
            ],
            Command::INVALID,
            'File "/app/tests/input/foo.csv" does not exist',
        ];

        yield [
            [
                'zone' => 'foo.test',
                'file' => 'dns_empty.csv',
            ],
            Command::INVALID,
            'No record found in file "/app/tests/input/dns_empty.csv".',
        ];

        yield [
            [
                'zone' => 'foo.test',
                'file' => 'dns_no_header.csv',
            ],
            Command::FAILURE,
            'The header record does not exist or is empty at offset: `0`',
        ];
    }
}
