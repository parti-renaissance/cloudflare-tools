<?php

namespace App\Cloudflare;

use App\Cloudflare\Contracts\Client as ClientInterface;
use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Auth\APIToken;
use Cloudflare\API\Endpoints\DNS;
use Cloudflare\API\Endpoints\Zones;

class Client implements ClientInterface
{
    private Guzzle $adapter;

    private ?Zones $zonesClient = null;
    private ?DNS $dnsClient = null;

    public function __construct(string $cloudflareApiToken)
    {
        $this->adapter = new Guzzle(new APIToken($cloudflareApiToken));
    }

    public function getZones(): array
    {
        return $this->getZonesClient()->listZones()->result;
    }

    public function getDnsRecords(
        string $zoneId,
        string $type = null,
        string $name = null,
        string $content = null,
        int $page = 1,
        int $perPage = 1000
    ): array {
        return $this->getDnsClient()->listRecords(
            $zoneId,
            (string) $type,
            (string) $name,
            (string) $content,
            $page,
            $perPage
        )->result;
    }

    public function addDnsRecord(
        string $zoneId,
        string $type,
        string $name,
        string $content,
        int $ttl,
        bool $proxied
    ): string {
        $success = $this->getDnsClient()->addRecord(
            $zoneId,
            $type,
            $name,
            $content,
            $ttl,
            $proxied
        );

        if (!$success) {
            throw new \RuntimeException(sprintf('Error while creating DNS record: "%s %s"', $type, $name));
        }

        return $this->getDnsClient()->getBody()->result->id;
    }

    public function updateDnsRecord(
        string $zoneId,
        string $dnsRecordId,
        string $type,
        string $name,
        string $content,
        int $ttl,
        bool $proxied
    ): void {
        $this->getDnsClient()->updateRecordDetails(
            $zoneId,
            $dnsRecordId,
            [
                'type' => $type,
                'name' => $name,
                'content' => $content,
                'ttl' => $ttl,
                'proxied' => $proxied,
            ]
        );
    }

    public function purgeCache(string $zoneId): void
    {
        $this->getZonesClient()->cachePurgeEverything($zoneId);
    }

    private function getZonesClient(): Zones
    {
        if (!$this->zonesClient) {
            $this->zonesClient = new Zones($this->adapter);
        }

        return $this->zonesClient;
    }

    private function getDnsClient(): DNS
    {
        if (!$this->dnsClient) {
            $this->dnsClient = new DNS($this->adapter);
        }

        return $this->dnsClient;
    }
}
