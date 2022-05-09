<?php

namespace App\Cloudflare\DnsRecord;

use App\Cloudflare\Model\DnsRecord;
use App\IO\Filesystem;
use League\Csv\Writer;

class Exporter
{
    private const OUTPUT_HEADERS = [
        'type',
        'name',
        'content',
        'ttl',
        'proxied',
    ];

    private Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function export(array $dnsRecords, string $filePrefix): string
    {
        $writer = $this->createWriter($this->createFilename($filePrefix));

        $writer->insertOne(self::OUTPUT_HEADERS);

        $writer->insertAll(array_map(function (DnsRecord $dnsRecord): array {
            return array_combine(self::OUTPUT_HEADERS, [
                $dnsRecord->getType(),
                $dnsRecord->getName(),
                $dnsRecord->getContent(),
                $dnsRecord->getTtl(),
                $dnsRecord->isProxied(),
            ]);
        }, $dnsRecords));

        return $writer->getPathname();
    }

    private function createFilename(string $filePrefix): string
    {
        return sprintf(
            '%s/%s_%s.csv',
            $this->filesystem->getOutputDir(),
            $filePrefix,
            date('YmdHis')
        );
    }

    private function createWriter(string $filename): Writer
    {
        return Writer::createFromPath($filename, 'w+');
    }
}
