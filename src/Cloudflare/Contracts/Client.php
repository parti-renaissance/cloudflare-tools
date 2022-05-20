<?php

namespace App\Cloudflare\Contracts;

interface Client
{
    /**
     * @return object[]|array
     */
    public function getZones(): array;

    /**
     * @return object[]|array
     */
    public function getDnsRecords(
        string $zoneId,
        string $type = null,
        string $name = null,
        string $content = null,
        int $page = 1,
        int $perPage = 1000
    ): array;

    /**
     * @return string the id of the created DNS record
     */
    public function addDnsRecord(
        string $zoneId,
        string $type,
        string $name,
        string $content,
        int $ttl,
        bool $proxied
    ): string;

    public function updateDnsRecord(
        string $zoneId,
        string $dnsRecordId,
        string $type,
        string $name,
        string $content,
        int $ttl,
        bool $proxied
    ): void;

    public function purgeCache(string $zoneId): void;
}
