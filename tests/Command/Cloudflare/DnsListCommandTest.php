<?php

namespace App\Tests\Command\Cloudflare;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;

class DnsListCommandTest extends CloudflareCommandTestCase
{
    public function getCommandName(): string
    {
        return 'cloudflare:dns:list';
    }

    /**
     * @dataProvider successCommandDataProvider
     */
    public function testSuccessCommand(
        array $existingDnsRecords,
        ?string $type,
        ?string $name,
        ?string $content,
        array $expectedDnsRecords = []
    ): void {
        $this->setAvailableZones([['id' => 'abc123', 'name' => 'foo.test', 'status' => 'active']]);
        $this->setAvailableDnsRecords($existingDnsRecords);

        $commandTester = $this->executeCommand(array_filter([
            'zone' => 'foo.test',
            '--type' => $type,
            '--name' => $name,
            '--content' => $content,
        ]));

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $output = $commandTester->getDisplay();

        if (empty($expectedDnsRecords)) {
            self::assertStringContainsString('No DNS record found', $output);

            return;
        }

        self::assertStringContainsString(sprintf('%d DNS record(s):', count($expectedDnsRecords)), $output);

        foreach ($expectedDnsRecords as $dnsRecord) {
            self::assertMatchesRegularExpression(
                sprintf(
                    '/%s\s+%s\s+%s/',
                    preg_quote($dnsRecord['type']),
                    preg_quote($dnsRecord['name']),
                    preg_quote($dnsRecord['content'])
                ),
                $output
            );
        }
    }

    public function successCommandDataProvider(): iterable
    {
        // No existing DNS record with no filter
        yield [
            [],
            null,
            null,
            null,
            [],
        ];

        // One existing DNS record with no filter
        yield [
            [
                ['id' => 'abc123', 'type' => 'A', 'name' => 'foo.test', 'content' => '127.0.0.1'],
            ],
            null,
            null,
            null,
            [
                ['id' => 'abc123', 'type' => 'A', 'name' => 'foo.test', 'content' => '127.0.0.1'],
            ],
        ];

        $existingDnsRecords = [
            ['id' => 'abc123', 'type' => 'A', 'name' => 'foo.test', 'content' => '127.0.0.1'],
            ['id' => 'def456', 'type' => 'A', 'name' => 'www.foo.test', 'content' => '127.0.0.1'],
            ['id' => 'ghi789', 'type' => 'CNAME', 'name' => 'bar.foo.test', 'content' => 'bar.test'],
        ];

        // Few existing DNS records with no filter
        yield [
            $existingDnsRecords,
            null,
            null,
            null,
            $existingDnsRecords,
        ];

        // Few existing DNS records filtered on "A" type
        yield [
            $existingDnsRecords,
            'A',
            null,
            null,
            [
                ['id' => 'abc123', 'type' => 'A', 'name' => 'foo.test', 'content' => '127.0.0.1'],
                ['id' => 'def456', 'type' => 'A', 'name' => 'www.foo.test', 'content' => '127.0.0.1'],
            ],
        ];

        // Few existing DNS records filtered on "CNAME" type
        yield [
            $existingDnsRecords,
            'CNAME',
            null,
            null,
            [
                ['id' => 'ghi789', 'type' => 'CNAME', 'name' => 'bar.foo.test', 'content' => 'bar.test'],
            ],
        ];

        // Few existing DNS records filtered on "www" name
        yield [
            $existingDnsRecords,
            null,
            'www',
            null,
            [
                ['id' => 'def456', 'type' => 'A', 'name' => 'www.foo.test', 'content' => '127.0.0.1'],
            ],
        ];

        // Few existing DNS records filtered on "bar.foo" name
        yield [
            $existingDnsRecords,
            null,
            'bar.foo',
            null,
            [
                ['id' => 'ghi789', 'type' => 'CNAME', 'name' => 'bar.foo.test', 'content' => 'bar.test'],
            ],
        ];

        // Few existing DNS records filtered on "foo.test" name
        yield [
            $existingDnsRecords,
            null,
            'foo.test',
            null,
            [
                ['id' => 'abc123', 'type' => 'A', 'name' => 'foo.test', 'content' => '127.0.0.1'],
                ['id' => 'def456', 'type' => 'A', 'name' => 'www.foo.test', 'content' => '127.0.0.1'],
                ['id' => 'ghi789', 'type' => 'CNAME', 'name' => 'bar.foo.test', 'content' => 'bar.test'],
            ],
        ];

        // Few existing DNS records filtered on "127" content
        yield [
            $existingDnsRecords,
            null,
            null,
            '127',
            [
                ['id' => 'abc123', 'type' => 'A', 'name' => 'foo.test', 'content' => '127.0.0.1'],
                ['id' => 'def456', 'type' => 'A', 'name' => 'www.foo.test', 'content' => '127.0.0.1'],
            ],
        ];

        // Few existing DNS records filtered on "0.0.1" content
        yield [
            $existingDnsRecords,
            null,
            null,
            '0.0.1',
            [
                ['id' => 'abc123', 'type' => 'A', 'name' => 'foo.test', 'content' => '127.0.0.1'],
                ['id' => 'def456', 'type' => 'A', 'name' => 'www.foo.test', 'content' => '127.0.0.1'],
            ],
        ];

        // Few existing DNS records filtered on "bat.test" content
        yield [
            $existingDnsRecords,
            null,
            null,
            'bar.test',
            [
                ['id' => 'ghi789', 'type' => 'CNAME', 'name' => 'bar.foo.test', 'content' => 'bar.test'],
            ],
        ];

        // Few existing DNS records filtered on "A" type, "foo.test" name and "127.0.0.1" content
        yield [
            $existingDnsRecords,
            'A',
            'foo.test',
            '127.0.0.1',
            [
                ['id' => 'abc123', 'type' => 'A', 'name' => 'foo.test', 'content' => '127.0.0.1'],
                ['id' => 'def456', 'type' => 'A', 'name' => 'www.foo.test', 'content' => '127.0.0.1'],
            ],
        ];

        // Few existing DNS records filtered on "A" type, "www" name and "127" content
        yield [
            $existingDnsRecords,
            'A',
            'www',
            '127',
            [
                ['id' => 'def456', 'type' => 'A', 'name' => 'www.foo.test', 'content' => '127.0.0.1'],
            ],
        ];
    }

    public function testCommandWithoutZone(): void
    {
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Not enough arguments (missing: "zone").');

        $this->executeCommand();
    }
}
