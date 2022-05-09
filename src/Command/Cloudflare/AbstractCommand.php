<?php

namespace App\Command\Cloudflare;

use App\Cloudflare\Contracts\Cloudflare;
use App\Cloudflare\Model\DnsRecord;
use App\Cloudflare\Model\Zone;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractCommand extends Command
{
    protected Cloudflare $cloudflare;
    protected ?SymfonyStyle $io = null;

    /** @required */
    public function setCloudflare(Cloudflare $cloudflare): void
    {
        $this->cloudflare = $cloudflare;
    }

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function askZoneQuestion(): string
    {
        $this->io->section('Zone selection');

        $availableZones = $this->cloudflare->findZones();

        $this->io->text('Available zones:');

        $this->displayZones($availableZones);

        return $this->io->askQuestion(new ChoiceQuestion('Select a zone:', $availableZones));
    }

    protected function displayZones(array $zones): void
    {
        $this->io->table(['Name', 'ID', 'Status'], array_map(function (Zone $zone): array {
            return [
                $zone->getName(),
                $zone->getId(),
                $zone->getStatus(),
            ];
        }, $zones));
    }

    protected function displayDnsRecords(array $dnsRecords): void
    {
        $this->io->table(['Type', 'Name', 'Content'], array_map(function (DnsRecord $dnsRecord): array {
            return [
                $dnsRecord->getType(),
                $dnsRecord->getName(),
                $dnsRecord->getShortContent(),
            ];
        }, $dnsRecords));
    }
}
