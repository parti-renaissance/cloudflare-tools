<?php

namespace App\Command\Cloudflare;

use App\Cloudflare\DnsRecord\Exporter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DnsListCommand extends AbstractCommand
{
    protected static $defaultName = 'cloudflare:dns:list';

    private Exporter $exporter;

    public function __construct(Exporter $exporter)
    {
        $this->exporter = $exporter;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('List DNS records for a given zone')
            ->addArgument('zone', InputArgument::REQUIRED, 'Zone name for DNS lsiting')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'DNS records type to filter')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'DNS records name to filter')
            ->addOption('content', null, InputOption::VALUE_OPTIONAL, 'DNS records content to filter')
            ->addOption('export', null, InputOption::VALUE_NONE, 'Add this option to export the filtered list to a CSV')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command lists DNS records for a given zone:

  <info>%command.full_name%</info>

You must specify the <comment>zone</comment> as an argument to bypass the zone selection prompt:

  <info>%command.full_name% domain.net</info>

Only zones granted by the <comment>CLOUDFLARE_API_TOKEN</comment> environment variable will be allowed.

You can also filter the DNS records by exact <comment>type</comment> by using the <comment>--type</comment> option:

  <info>%command.full_name% --type=CNAME</info>

You can also filter the DNS records containing a given <comment>name</comment> by using the <comment>--name</comment> option:

  <info>%command.full_name% --name=test.domain.net</info>

You can also filter the DNS records containing a given <comment>content</comment> by using the <comment>--content</comment> option:

  <info>%command.full_name% --content=127.0.0.1</info>

You can add the <comment>--export</comment> option to export the filtered list to a CSV:

  <info>%command.full_name% --export</info>
EOF
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('zone')) {
            $input->setArgument('zone', $this->askZoneQuestion());
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('DNS records listing');

        $zoneName = $input->getArgument('zone');

        if (!$zone = $this->cloudflare->findZone($zoneName)) {
            $this->io->error("No zone found with name \"$zoneName\".");

            return self::INVALID;
        }

        $this->io->text('Zone details:');

        $this->displayZones([$zone]);

        $this->io->section("Listing DNS records for zone <info>$zone</info>:");

        $filters = [];

        if ($filterType = $input->getOption('type')) {
            $filters[] = "<comment>type</comment> equals: <info>$filterType</info>";
        }

        if ($filterName = $input->getOption('name')) {
            $filters[] = "<comment>name</comment> contains: <info>$filterName</info>";
        }

        if ($filterContent = $input->getOption('content')) {
            $filters[] = "<comment>content</comment> contains: <info>$filterContent</info>";
        }

        if (!empty($filters)) {
            $this->io->comment('Active filters:');
            $this->io->listing($filters);
        }

        $dnsRecords = $this->cloudflare->findDnsRecords($zone, $filterType, $filterName, $filterContent);

        if (empty($dnsRecords)) {
            $this->io->info('No DNS record found.');

            return self::SUCCESS;
        }

        $this->io->comment(sprintf('<comment>%d</comment> DNS record(s):', count($dnsRecords)));

        $this->displayDnsRecords($dnsRecords);

        if ($input->getOption('export')) {
            $outputFilename = $this->exporter->export($dnsRecords, 'cloudflare_dns_list');

            $this->io->comment("Exported to file: <info>$outputFilename</info>");
        }

        return self::SUCCESS;
    }
}
