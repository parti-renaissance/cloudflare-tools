<?php

namespace App\Cloudflare;

use App\Cloudflare\Contracts\Cloudflare as CloudflareInterface;
use App\Cloudflare\Contracts\Manager;
use App\Cloudflare\Model\DnsRecord;
use App\Cloudflare\Model\DnsRecordTypeEnum;
use App\Cloudflare\Model\Zone;
use Psr\Log\LoggerInterface;

class Cloudflare implements CloudflareInterface
{
    private Manager $manager;
    private LoggerInterface $logger;

    public function __construct(Manager $manager, LoggerInterface $cloudflareLogger)
    {
        $this->manager = $manager;
        $this->logger = $cloudflareLogger;
    }

    public function findZones(): array
    {
        return $this->manager->getZones();
    }

    public function findZone(string $name): ?Zone
    {
        foreach ($this->manager->getZones() as $zone) {
            if ($zone->getName() === $name) {
                return $zone;
            }
        }

        return null;
    }

    public function findDnsRecords(Zone $zone, string $type = null, string $name = null, string $content = null): array
    {
        $dnsRecords = $this->manager->getDnsRecords($zone);

        if ($type) {
            $dnsRecords = array_filter($dnsRecords, function (DnsRecord $dnsRecord) use ($type): bool {
                return $dnsRecord->getType() === $type;
            });
        }

        if ($name) {
            $dnsRecords = array_filter($dnsRecords, function (DnsRecord $dnsRecord) use ($name): bool {
                return str_contains($dnsRecord->getName(), $name);
            });
        }

        if ($content) {
            $dnsRecords = array_filter($dnsRecords, function (DnsRecord $dnsRecord) use ($content): bool {
                return str_contains($dnsRecord->getContent(), $content);
            });
        }

        return $dnsRecords;
    }

    public function findDnsRecord(
        Zone $zone,
        string $type = null,
        string $name = null,
        string $content = null
    ): ?DnsRecord {
        $dnsRecords = $this->findDnsRecords(
            $zone,
            $type,
            $name,
            DnsRecordTypeEnum::TYPE_TXT === $type ? $content : null
        );

        return !empty($dnsRecords) ? reset($dnsRecords) : null;
    }

    public function saveDnsRecord(DnsRecord $dnsRecord): void
    {
        $this->manager->saveDnsRecord($dnsRecord);
    }
}
