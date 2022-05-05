<?php

namespace App\Cloudflare\Model;

class DnsRecord
{
    public const DEFAULT_TTL = 0;
    public const DEFAULT_PROXIED = true;

    private Zone $zone;
    private ?string $id;
    private string $type;
    private string $name;
    private string $content;
    private int $ttl;
    private bool $proxied;

    public function __construct(
        Zone $zone,
        ?string $id,
        string $type,
        string $name,
        string $content,
        int $ttl,
        bool $proxied
    ) {
        $this->zone = $zone;
        $this->id = $id;
        $this->type = $type;
        $this->name = $name;
        $this->content = $content;
        $this->ttl = $ttl;
        $this->proxied = $proxied;
    }

    public function __toString(): string
    {
        return sprintf('[%s] %s %s', $this->type, $this->name, $this->getShortContent());
    }

    public function getZone(): Zone
    {
        return $this->zone;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getShortContent(): string
    {
        return strlen($this->content) > 50 ? substr($this->content, 0, 35).'...' : $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function isProxied(): bool
    {
        return $this->proxied;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function needsUpdate(string $content): bool
    {
        return $this->content !== $content;
    }

    public function update(string $content): void
    {
        $this->content = $content;
    }
}
