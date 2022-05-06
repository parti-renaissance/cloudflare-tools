<?php

namespace App\Cloudflare\Contracts;

use App\Cloudflare\Model\DnsRecord;
use App\Cloudflare\Model\Zone;

interface Manager
{
    /** @return Zone[]|array */
    public function getZones(): array;

    /** @return DnsRecord[]|array */
    public function getDnsRecords(Zone $zone): array;

    public function createDnsRecord(
        Zone $zone,
        string $type,
        string $name,
        string $content,
        ?int $ttl,
        ?bool $proxied
    ): DnsRecord;

    public function saveDnsRecord(DnsRecord $dnsRecord): void;
}