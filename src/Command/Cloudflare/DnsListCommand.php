<?php

namespace App\Command\Cloudflare;

use App\Cloudflare\Model\DnsRecord;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DnsListCommand extends AbstractCommand
{
    public static $defaultName = 'cloudflare:dns:list';

    protected function configure()
    {
        $this
            ->setDescription('List DNS records for a given zone')
            ->addArgument('zone', InputArgument::OPTIONAL, 'Zone name for DNS lsiting')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'DNS records type to filter')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'DNS records name to filter')
            ->addOption('content', null, InputOption::VALUE_OPTIONAL, 'DNS records content to filter')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command lists DNS records for a given zone:

  <info>%command.full_name%</info>

You can specify the <comment>zone</comment> as an argument to bypass the zone selection prompt:

  <info>%command.full_name% domain.net</info>

You can also filter the DNS records by exact <comment>type</comment> by using the <comment>--type</comment> option:

  <info>%command.full_name% --type=CNAME</info>

You can also filter the DNS records containing a given <comment>name</comment> by using the <comment>--name</comment> option:

  <info>%command.full_name% --name=test.domain.net</info>

You can also filter the DNS records containing a given <comment>content</comment> by using the <comment>--content</comment> option:

  <info>%command.full_name% --content=127.0.0.1</info>

Only zones granted by the <comment>CLOUDFLARE_API_TOKEN</comment> environment variable will be allowed.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('DNS records listing');

        $zone = $this->selectZone($input);

        if (!$zone) {
            $this->io->error('No zone provided.');

            return self::INVALID;
        }

        $this->io->section(sprintf('Listing DNS records for zone <info>%s</info>:', $zone));

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
            $this->io->text('No DNS record found.');

            return self::SUCCESS;
        }

        $this->io->text(sprintf('<comment>%d</comment> DNS record(s):', count($dnsRecords)));

        $this->io->table(['Type', 'Name', 'Content'], array_map(function (DnsRecord $dnsRecord): array {
            return [
                $dnsRecord->getType(),
                $dnsRecord->getName(),
                $dnsRecord->getShortContent(),
            ];
        }, $dnsRecords));

        return self::SUCCESS;
    }
}
