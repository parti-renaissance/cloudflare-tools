<?php

namespace App\Cloudflare\Contracts;

use App\Cloudflare\Model\DnsRecord;
use App\Cloudflare\Model\Zone;

interface Cloudflare
{
    /** @return Zone[]|array */
    public function findZones(): array;

    public function findZone(string $name): ?Zone;

    /** @return DnsRecord[]|array */
    public function findDnsRecords(Zone $zone, string $type = null, string $name = null, string $content = null): array;

    public function findDnsRecord(
        Zone $zone,
        string $type = null,
        string $name = null,
        string $content = null
    ): ?DnsRecord;

    public function importDnsRecord(
        Zone $zone,
        string $type,
        string $name,
        string $content,
        ?int $ttl,
        ?bool $proxied
    ): void;
}
