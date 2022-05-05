<?php

namespace App\Cloudflare\Factory;

use App\Cloudflare\Model\DnsRecord;
use App\Cloudflare\Model\DnsRecordTypeEnum;
use App\Cloudflare\Model\Zone;

class DnsRecordFactory
{
    public static function create(
        Zone $zone,
        ?string $id,
        string $type,
        string $name,
        string $content,
        ?int $ttl,
        ?bool $proxied
    ): DnsRecord {
        $ttl = $ttl ?? DnsRecord::DEFAULT_TTL;
        $proxied = $proxied ?? DnsRecord::DEFAULT_PROXIED;

        if (!in_array($type, DnsRecordTypeEnum::ALL, true)) {
            throw new \InvalidArgumentException(sprintf('DNS record type "%s" is not handled.', $type));
        }

        return new DnsRecord(
            $zone,
            $id,
            $type,
            $name,
            $content,
            $ttl,
            $proxied
        );
    }
}
