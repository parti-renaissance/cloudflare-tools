<?php

namespace App\Tests\Test\Cloudflare;

use App\Cloudflare\Contracts\Client;

class DummyClient implements Client
{
    private array $zones = [];
    private array $dnsRecords = [];

    public function getZones(): array
    {
        return $this->zones;
    }

    public function getDnsRecords(
        string $zoneId,
        string $type = null,
        string $name = null,
        string $content = null,
        int $page = 1,
        int $perPage = 1000
    ): array {
        return $this->dnsRecords;
    }

    public function addDnsRecord(
        string $zoneId,
        string $type,
        string $name,
        string $content,
        int $ttl,
        bool $proxied
    ): string {
        return uniqid();
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
    }

    public function setZones(array $zones): void
    {
        $this->zones = [];

        foreach ($zones as $zone) {
            $this->zones[] = (object) $zone;
        }
    }

    public function setDnsRecords(array $dnsRecords): void
    {
        $this->dnsRecords = [];

        foreach ($dnsRecords as $dnsRecord) {
            $this->dnsRecords[] = (object) $dnsRecord;
        }
    }
}
