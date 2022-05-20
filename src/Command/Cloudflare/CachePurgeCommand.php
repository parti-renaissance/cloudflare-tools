<?php

namespace App\Command\Cloudflare;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CachePurgeCommand extends AbstractCommand
{
    protected static $defaultName = 'cloudflare:cache:purge';

    protected function configure()
    {
        $this
            ->setDescription('Purge cache for a given zone')
            ->addArgument('zone', InputArgument::REQUIRED, 'Zone name for DNS lsiting')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command purges the cache for a given zone:

  <info>%command.full_name%</info>

You must specify the <comment>zone</comment> as an argument to bypass the zone selection prompt:

  <info>%command.full_name% domain.net</info>

Only zones granted by the <comment>CLOUDFLARE_API_TOKEN</comment> environment variable will be allowed.
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
        $this->io->title('Cache purge');

        $zoneName = $input->getArgument('zone');

        if (!$zone = $this->cloudflare->findZone($zoneName)) {
            $this->io->error("No zone found with name \"$zoneName\".");

            return self::INVALID;
        }

        $this->io->section("Purging cache for zone <info>$zone</info>:");

        if (!$this->io->confirm('Do you want to continue ?')) {
            return self::FAILURE;
        }

        $this->cloudflare->purgeCache($zone);

        $this->io->success('Cache purged successfully!');

        return self::SUCCESS;
    }
}
