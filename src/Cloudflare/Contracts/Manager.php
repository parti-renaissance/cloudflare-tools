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

    public function persistDnsRecord(DnsRecord $dnsRecord): void;

    public function flush(): void;

    public function purgeCache(Zone $zone): void;
}
