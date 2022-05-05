<?php

namespace App\Cloudflare\Model;

class DnsRecordTypeEnum
{
    public const TYPE_A = 'A';
    public const TYPE_AAAA = 'AAAA';
    public const TYPE_CNAME = 'CNAME';
    public const TYPE_TXT = 'TXT';
    public const TYPE_MX = 'MX';

    public const ALL = [
        self::TYPE_A,
        self::TYPE_AAAA,
        self::TYPE_CNAME,
        self::TYPE_TXT,
        self::TYPE_MX,
    ];
}
