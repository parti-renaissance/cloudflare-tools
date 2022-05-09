<?php

namespace App\Tests\Command\Cloudflare;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;

class DnsImportCommandTest extends CloudflareCommandTestCase
{
    public function getCommandName(): string
    {
        return 'cloudflare:dns:import';
    }

    public function testSuccessCommand(): void
    {
        $this->setAvailableZones([['id' => 'abc123', 'name' => 'foo.test', 'status' => 'active']]);

        $commandTester = $this->executeCommand([
            'zone' => 'foo.test',
            'file' => 'dns.csv',
        ]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        self::assertStringContainsString('Successfully processed 4 DNS records.', $commandTester->getDisplay());
    }

    /**
     * @dataProvider missingArgumentsDataProvider
     */
    public function testMissingArguments(array $arguments, string $expectedErrorMessage): void
    {
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage($expectedErrorMessage);

        $this->executeCommand($arguments);
    }

    public function missingArgumentsDataProvider(): iterable
    {
        yield [
            [],
            'Not enough arguments (missing: "zone, file").',
        ];
    }

    /**
     * @dataProvider invalidArgumentsDataProvider
     */
    public function testInvalidArguments(array $arguments, string $expectedErrorMessage): void
    {
        $this->setAvailableZones([['id' => 'abc123', 'name' => 'foo.test', 'status' => 'active']]);

        $commandTester = $this->executeCommand($arguments);

        self::assertSame(Command::INVALID, $commandTester->getStatusCode());
        self::assertStringContainsString($expectedErrorMessage, $commandTester->getDisplay());
    }

    public function invalidArgumentsDataProvider(): iterable
    {
        yield [
            [
                'zone' => 'foo.test',
                'file' => 'foo.csv',
            ],
            'File "foo.csv" does not exist',
        ];
        yield [
            [
                'zone' => 'foo.test',
                'file' => 'dns_empty.csv',
            ],
            'No record found in file "dns_empty.csv".',
        ];

        yield [
            [
                'zone' => 'foo.test',
                'file' => 'dns_no_header.csv',
            ],
            'The header record does not exist or is empty at offset: `0`',
        ];
    }
}
