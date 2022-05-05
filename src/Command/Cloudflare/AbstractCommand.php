<?php

namespace App\Command\Cloudflare;

use App\Cloudflare\Contracts\Cloudflare;
use App\Cloudflare\Model\Zone;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractCommand extends Command
{
    protected Cloudflare $cloudflare;
    protected string $inputDir;
    protected ?SymfonyStyle $io = null;

    public function __construct(Cloudflare $cloudflare, string $inputDir)
    {
        $this->cloudflare = $cloudflare;
        $this->inputDir = $inputDir;

        parent::__construct();
    }

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function selectZone(InputInterface $input): ?Zone
    {
        if (!($defaultZone = $input->getArgument('zone')) && !$input->isInteractive()) {
            return null;
        }

        if ($defaultZone && $zone = $this->cloudflare->findZone($defaultZone)) {
            return $zone;
        }

        $availableZones = $this->cloudflare->findZones();

        $question = (new ChoiceQuestion('Select a zone:', $availableZones, $defaultZone))
            ->setErrorMessage(implode(PHP_EOL, [
                'There is no zone with name "%s".',
                sprintf('Available zones are: %s.', implode(', ', array_map(function (Zone $zone): string {
                    return sprintf('"%s"', $zone->getName());
                }, $availableZones))),
            ]))
        ;

        return $this->io->askQuestion($question);
    }
}
