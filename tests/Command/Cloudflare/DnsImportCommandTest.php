<?php

namespace App\Tests\Command\Cloudflare;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;

class DnsImportCommandTest extends CloudflareCommandTestCase
{
    private ?string $inputDir = null;

    public function getCommandName(): string
    {
        return 'cloudflare:dns:import';
    }

    public function testSuccessCommand(): void
    {
        $this->setAvailableZones([['id' => 'abc123', 'name' => 'foo.test', 'status' => 'active']]);
        $this->createInputFile(<<<'CSV'
type,name,content
A,foo.test,157.230.77.126
A,bar.test,141.94.219.130
A,foo.test,157.230.77.126
A,bar.test,141.94.219.131
CSV
        );

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

        yield [
            [
                'zone' => 'foo.test',
            ],
            'Not enough arguments (missing: "file").',
        ];
        yield [
            [
                'file' => 'file.csv',
            ],
            'Not enough arguments (missing: "zone").',
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
    }

    /**
     * @dataProvider invalidFileDataProvider
     */
    public function testInvalidFile(string $csvAsString, string $expectedErrorMessage): void
    {
        $inputDir = $this->getContainer()->getParameter('input_dir');

        $this->setAvailableZones([['id' => 'abc123', 'name' => 'foo.test', 'status' => 'active']]);
        $this->createInputFile($csvAsString);

        $commandTester = $this->executeCommand([
            'zone' => 'foo.test',
            'file' => 'dns.csv',
        ]);

        self::assertSame(Command::INVALID, $commandTester->getStatusCode());
        self::assertStringContainsString($expectedErrorMessage, $commandTester->getDisplay());

        unlink("$inputDir/dns.csv");
    }

    public function invalidFileDataProvider(): iterable
    {
        yield [
            '',
            'The header record does not exist or is empty at offset: `0`',
        ];

        yield [<<<'CSV'
type,name,content
CSV,
            'No record found in file "dns.csv".',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->inputDir = $this->getContainer()->getParameter('input_dir');
    }

    private function createInputFile(string $csvAsString): void
    {
        file_put_contents(sprintf('%s/dns.csv', $this->inputDir), $csvAsString);
    }
}
