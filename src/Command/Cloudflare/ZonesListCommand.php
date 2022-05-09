<?php

namespace App\Command\Cloudflare;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ZonesListCommand extends AbstractCommand
{
    protected static $defaultName = 'cloudflare:zones:list';

    protected function configure()
    {
        $this
            ->setDescription('List available zones from Cloudflare')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command lists all zones available from Cloudflare:

  <info>%command.full_name%</info>

All zones granted by the <comment>CLOUDFLARE_API_TOKEN</comment> environment variable will be displayed.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Listing zones managed by API Token');

        $zones = $this->cloudflare->findZones();

        if (empty($zones)) {
            $this->io->text('No zone found.');

            return self::SUCCESS;
        }

        $this->io->comment(sprintf('<comment>%d</comment> zone(s):', count($zones)));

        $this->displayZones($zones);

        return self::SUCCESS;
    }
}
