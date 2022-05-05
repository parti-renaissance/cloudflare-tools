<?php

namespace App\Cloudflare\Factory;

use App\Cloudflare\Model\Zone;

class ZoneFactory
{
    public static function create(
        string $id,
        string $name,
        string $status,
        array $nameServers = [],
        array $originalNameServers = []
    ): Zone {
        return new Zone($id, $name, $status, $nameServers, $originalNameServers);
    }
}
