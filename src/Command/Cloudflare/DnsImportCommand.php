<?php

namespace App\Command\Cloudflare;

use App\Command\CsvCommandTrait;
use League\Csv\SyntaxError;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DnsImportCommand extends AbstractCommand
{
    use CsvCommandTrait;

    public static $defaultName = 'cloudflare:dns:import';

    protected function configure()
    {
        $this
            ->setDescription('Import DNS records from a CSV file')
            ->addArgument('file', InputArgument::OPTIONAL, 'Location of the file to import.')
            ->addArgument('zone', InputArgument::OPTIONAL, 'The zone name')
            ->addOption('csv-delimiter', null, InputOption::VALUE_OPTIONAL, 'Delimiter of the input CSV file', ',')
            ->addOption('type-column', null, InputOption::VALUE_OPTIONAL, 'Name of the "type" column in the input CSV file', 'type')
            ->addOption('name-column', null, InputOption::VALUE_OPTIONAL, 'Name of the "name" column in the input CSV file', 'name')
            ->addOption('content-column', null, InputOption::VALUE_OPTIONAL, 'Name of the "content" column in the input CSV file', 'content')
            ->addOption('ttl-column', null, InputOption::VALUE_OPTIONAL, 'Name of the "content" column in the input CSV file', 'ttl')
            ->addOption('proxied-column', null, InputOption::VALUE_OPTIONAL, 'Name of the "content" column in the input CSV file', 'proxied')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command imports DNS records from a CSV file:

  <info>%command.full_name% file.csv</info>

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

You can specify the <comment>zone</comment> as an argument to bypass the zone selection prompt:

  <info>%command.full_name% file.csv domain.net</info>

Only zones granted by the <comment>CLOUDFLARE_API_TOKEN</comment> environment variable will be allowed.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('DNS records import');

        if (!$filename = $input->getArgument('file')) {
            $this->io->error('No file provided.');

            return self::INVALID;
        }

        $zone = $this->selectZone($input);

        if (!$zone) {
            $this->io->error('No zone provided.');

            return self::INVALID;
        }

        $this->io->section(sprintf('Importing DNS records for zone <info>%s</info>', $zone));

        $filename = $this->inputDir.'/'.$filename;

        if (!file_exists($filename)) {
            $this->io->error(sprintf('File "%s" does not exist.', $filename));

            return self::INVALID;
        }

        $this->io->comment("Reading file: <info>$filename</info>");

        try {
            $csv = self::readCsv($filename, 0, $input->getOption('csv-delimiter'));

            $total = $csv->count();
        } catch (SyntaxError $error) {
            $this->io->error($error->getMessage());

            return self::FAILURE;
        }

        if (!$total) {
            $this->io->note(sprintf('No record found in file "%s".', $filename));

            return self::INVALID;
        }

        if (!$this->io->confirm(sprintf('Are you sure to import <comment>%d</comment> DNS records?', $total))) {
            return self::FAILURE;
        }

        $this->io->progressStart($total);

        foreach ($csv as $row) {
            $type = $row[$input->getOption('type-column')];
            $name = $row[$input->getOption('name-column')];
            $content = $row[$input->getOption('content-column')];
            $ttl = $row[$input->getOption('ttl-column')] ?? null;
            $proxied = $row[$input->getOption('proxied-column')] ?? null;

            $this->cloudflare->importDnsRecord($zone, $type, $name, $content, $ttl, $proxied);

            $this->io->progressAdvance();
        }

        $this->io->progressFinish();

        $this->io->success(sprintf('Successfully processed %d DNS records.', $total));

        return self::SUCCESS;
    }
}
