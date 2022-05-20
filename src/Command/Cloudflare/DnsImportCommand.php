<?php

namespace App\Command\Cloudflare;

use App\Cloudflare\DnsRecord\Importer;
use App\IO\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class DnsImportCommand extends AbstractCommand
{
    protected static $defaultName = 'cloudflare:dns:import';

    private Importer $importer;
    private Filesystem $filesystem;

    public function __construct(Importer $importer, Filesystem $filesystem)
    {
        $this->importer = $importer;
        $this->filesystem = $filesystem;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import DNS records from a CSV file')
            ->addArgument('zone', InputArgument::REQUIRED, 'The zone name')
            ->addArgument('file', InputArgument::REQUIRED, 'File to import (relative to input directry)')
            ->addOption('csv-delimiter', null, InputOption::VALUE_OPTIONAL, 'Delimiter of the input CSV file', ',')
            ->addOption('type-column', null, InputOption::VALUE_OPTIONAL, 'Name of the "type" column in the input CSV file', 'type')
            ->addOption('name-column', null, InputOption::VALUE_OPTIONAL, 'Name of the "name" column in the input CSV file', 'name')
            ->addOption('content-column', null, InputOption::VALUE_OPTIONAL, 'Name of the "content" column in the input CSV file', 'content')
            ->addOption('ttl-column', null, InputOption::VALUE_OPTIONAL, 'Name of the "content" column in the input CSV file', 'ttl')
            ->addOption('proxied-column', null, InputOption::VALUE_OPTIONAL, 'Name of the "content" column in the input CSV file', 'proxied')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command imports DNS records from a CSV file:

  <info>%command.full_name% domain.test file.csv</info>

You must specify the <comment>zone</comment> as an argument to bypass the zone selection prompt:

  <info>%command.full_name% domain.net</info>

Only zones granted by the <comment>CLOUDFLARE_API_TOKEN</comment> environment variable will be allowed.

The CSV file must contain the following <comment>mandatory headers</comment>:

    <comment>type</comment>,<comment>name</comment>,<comment>content</comment>

The CSV file can contain the following <comment>optionnal headers</comment>:

    <comment>ttl</comment>,<comment>proxied</comment>

You can override the CSV delimiter and expected column names by using the following options:

    <comment>--csv-delimiter=,</comment>
    <comment>--type-column=type</comment>
    <comment>--name-column=name</comment>
    <comment>--content-column=content</comment>
    <comment>--ttl-column=ttl</comment>
    <comment>--proxied-column=proxied</comment>
EOF
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('zone')) {
            $input->setArgument('zone', $this->askZoneQuestion());
        }

        if (!$input->getArgument('file')) {
            $input->setArgument('file', $this->askFileQuestion());
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('DNS records import');

        $zoneName = $input->getArgument('zone');

        $this->io->section("Fetching zone: <info>$zoneName</info>");

        if (!$zone = $this->cloudflare->findZone($zoneName)) {
            $this->io->error("No zone found with name \"$zoneName\".");

            return self::INVALID;
        }

        $this->io->text('Zone details:');

        $this->displayZones([$zone]);

        $filename = $input->getArgument('file');

        $this->io->section("Reading file: <info>$filename</info>");

        if (!$this->filesystem->fileExists($filename)) {
            $this->io->error("File \"$filename\" does not exist.");

            return self::INVALID;
        }

        try {
            $csv = $this->filesystem->readCsv($filename, $input->getOption('csv-delimiter'));

            $total = $csv->count();
        } catch (\Exception $error) {
            $this->io->error($error->getMessage());

            return self::INVALID;
        }

        if (0 === $total) {
            $this->io->note(sprintf('No record found in file "%s".', $filename));

            return self::INVALID;
        }

        $typeColumn = $input->getOption('type-column');
        $nameColumn = $input->getOption('name-column');
        $contentColumn = $input->getOption('content-column');
        $ttlColumn = $input->getOption('ttl-column');
        $proxiedColumn = $input->getOption('proxied-column');

        $this->io->text("Processing <comment>$total</comment> row(s)...");

        $this->io->progressStart($total);

        foreach ($csv as $row) {
            $type = $row[$typeColumn];
            $name = $row[$nameColumn];
            $content = $row[$contentColumn];
            $ttl = $row[$ttlColumn] ?? null;
            $proxied = $row[$proxiedColumn] ?? null;

            $this->importer->prepare($zone, $type, $name, $content, $ttl, $proxied);

            $this->io->progressAdvance();
        }

        $this->io->progressFinish();

        $dnsRecordsToCreate = $this->importer->listActionCreate();
        $dnsRecordsToUpdate = $this->importer->listActionUpdate();

        $createCount = count($dnsRecordsToCreate);
        $updateCount = count($dnsRecordsToUpdate);
        $noneCount = count($this->importer->listActionNone());

        $this->io->text('Import summary:');
        $this->io->listing([
            "<comment>$createCount</comment> DNS record(s) will be created",
            "<comment>$updateCount</comment> DNS record(s) will be updated",
            "<comment>$noneCount</comment> DNS record(s) will remain unchanged",
        ]);

        if (!empty($dnsRecordsToCreate)) {
            $this->io->text('Dns record(s) to create:');
            $this->displayDnsRecords($dnsRecordsToCreate);
        }

        if (!empty($dnsRecordsToUpdate)) {
            $this->io->text('Dns record(s) to update:');
            $this->displayDnsRecords($dnsRecordsToUpdate);
        }

        if (empty($dnsRecordsToCreate) && empty($dnsRecordsToUpdate)) {
            $this->io->success('No DNS record to create or update from this file.');

            return self::SUCCESS;
        }

        if (!$this->io->confirm('Do you want to continue?')) {
            return self::FAILURE;
        }

        $this->io->text('Flushing DNS record(s)...');

        $this->cloudflare->flush();

        $this->io->success("Successfully processed $total DNS records.");

        return self::SUCCESS;
    }

    private function askFileQuestion(): string
    {
        $this->io->section('File selection');

        return $this->io->askQuestion(new ChoiceQuestion('Select a file:', $this->filesystem->listFiles(['csv'])));
    }

    private function saveDnsRecords(array $dnsRecords): void
    {
        $this->io->progressStart(count($dnsRecords));

        foreach ($dnsRecords as $dnsRecord) {
            $this->cloudflare->saveDnsRecord($dnsRecord);

            $this->io->progressAdvance();
        }

        $this->io->progressFinish();
    }
}
