<?php

namespace App\Cloudflare;

use App\Cloudflare\Contracts\Client;
use App\Cloudflare\Contracts\Manager as ManagerInterface;
use App\Cloudflare\Model\DnsRecord;
use App\Cloudflare\Model\Zone;

class Manager implements ManagerInterface
{
    private Client $client;

    /** @var array|Zone[] */
    private array $cachedZones = [];
    /** @var array|DnsRecord[] */
    private array $cachedDnsRecords = [];

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getZones(): array
    {
        if (empty($this->cachedZones)) {
            $this->cachedZones = $this->fetchZones();
        }

        return $this->cachedZones;
    }

    public function getDnsRecords(Zone $zone): array
    {
        if (!array_key_exists($zone->getName(), $this->cachedDnsRecords)) {
            $this->cachedDnsRecords[$zone->getName()] = $this->fetchDnsRecords($zone);
        }

        return $this->cachedDnsRecords[$zone->getName()];
    }

    public function saveDnsRecord(DnsRecord $dnsRecord): void
    {
        if (!$dnsRecord->getId()) {
            $this->addDnsRecord($dnsRecord);

            return;
        }

        $this->updateDnsRecord($dnsRecord);
    }

    private function addDnsRecord(DnsRecord $dnsRecord): void
    {
        $dnsRecordId = $this->client->addDnsRecord(
            $dnsRecord->getZone()->getId(),
            $dnsRecord->getType(),
            $dnsRecord->getName(),
            $dnsRecord->getContent(),
            $dnsRecord->getTtl(),
            $dnsRecord->isProxied()
        );

        $dnsRecord->setId($dnsRecordId);

        $this->cachedDnsRecords[$dnsRecord->getZone()->getName()][] = $dnsRecord;
    }

    private function updateDnsRecord(DnsRecord $dnsRecord): void
    {
        $this->client->updateDnsRecord(
            $dnsRecord->getZone()->getId(),
            $dnsRecord->getId(),
            $dnsRecord->getType(),
            $dnsRecord->getName(),
            $dnsRecord->getContent(),
            $dnsRecord->getTtl(),
            $dnsRecord->isProxied()
        );
    }

    /** @return Zone[]|array */
    private function fetchZones(): array
    {
        return array_map(function (object $zoneResult): Zone {
            return new Zone(
                $zoneResult->id,
                $zoneResult->name,
                $zoneResult->status,
                $zoneResult->name_servers ?? [],
                $zoneResult->original_name_servers ?? []
            );
        }, $this->client->getZones());
    }

    /** @return DnsRecord[]|array */
    private function fetchDnsRecords(Zone $zone): array
    {
        return array_map(function (object $dnsRecordResult) use ($zone): DnsRecord {
            return new DnsRecord(
                $zone,
                $dnsRecordResult->id,
                $dnsRecordResult->type,
                $dnsRecordResult->name,
                $dnsRecordResult->content,
                $dnsRecordResult->ttl ?? null,
                $dnsRecordResult->proxied ?? null
            );
        }, $this->client->getDnsRecords($zone->getId()));
    }
}
