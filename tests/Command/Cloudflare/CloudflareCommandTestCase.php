<?php

namespace App\Tests\Command\Cloudflare;

use App\Cloudflare\Contracts\Client;
use App\Tests\CommandTestCase;
use App\Tests\Test\Cloudflare\DummyClient;

abstract class CloudflareCommandTestCase extends CommandTestCase
{
    private ?DummyClient $client = null;

    protected function setAvailableZones(array $zones): void
    {
        $this->client->setZones($zones);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $client = $this->getContainer()->get(Client::class);

        if (!$client instanceof DummyClient) {
            throw new \RuntimeException(sprintf('Service "%s" must be an instance of "%s". ("%s" given)', Client::class, DummyClient::class, get_class($client)));
        }

        $this->client = $client;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->client = null;
    }
}
