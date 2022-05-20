<?php

namespace App\Cloudflare\DnsRecord;

use App\Cloudflare\Contracts\Cloudflare;
use App\Cloudflare\Model\DnsRecord;
use App\Cloudflare\Model\Zone;

class Importer
{
    private const ACTION_CREATE = 'dns.action.create';
    private const ACTION_UPDATE = 'dns.action.update';
    private const ACTION_NONE = 'dns.action.none';

    private Cloudflare $cloudflare;
    private array $actions;

    public function __construct(Cloudflare $cloudflare)
    {
        $this->cloudflare = $cloudflare;

        $this->reset();
    }

    public function reset(): void
    {
        $this->actions = [
            self::ACTION_CREATE => [],
            self::ACTION_UPDATE => [],
            self::ACTION_NONE => [],
        ];
    }

    public function prepare(Zone $zone, string $type, string $name, string $content, ?int $ttl, ?bool $proxied): void
    {
        $dnsRecord = $this->cloudflare->findDnsRecord($zone, $type, $name, $content);

        if (!$dnsRecord) {
            $dnsRecord = $this->createDnsRecord($zone, $type, $name, $content, $ttl, $proxied);

            $this->cloudflare->persistDnsRecord($dnsRecord);

            $this->addAction($dnsRecord, self::ACTION_CREATE);

            return;
        }

        if ($dnsRecord->needsUpdate($content)) {
            $dnsRecord->update($content);

            $this->cloudflare->persistDnsRecord($dnsRecord);

            $this->addAction($dnsRecord, self::ACTION_UPDATE);

            return;
        }

        $this->addAction($dnsRecord, self::ACTION_NONE);
    }

    public function listActionCreate(): array
    {
        return $this->actions[self::ACTION_CREATE];
    }

    public function listActionUpdate(): array
    {
        return $this->actions[self::ACTION_UPDATE];
    }

    public function listActionNone(): array
    {
        return $this->actions[self::ACTION_NONE];
    }

    private function createDnsRecord(
        Zone $zone,
        string $type,
        string $name,
        string $content,
        ?int $ttl,
        ?bool $proxied
    ): DnsRecord {
        return new DnsRecord($zone, null, $type, $name, $content, $ttl, $proxied);
    }

    private function addAction(DnsRecord $dnsRecord, string $action): void
    {
        $this->actions[$action][] = $dnsRecord;
    }
}
