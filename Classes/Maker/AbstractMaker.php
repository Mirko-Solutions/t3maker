<?php

declare(strict_types=1);

namespace Mirko\T3maker\Maker;

use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractMaker implements MakerInterface
{
    /**
     * @param SymfonyStyle $io
     * @return void
     */
    protected function writeSuccessMessage(SymfonyStyle $io): void
    {
        $io->newLine();
        $io->writeln(' <bg=green;fg=white>          </>');
        $io->writeln(' <bg=green;fg=white> Success! </>');
        $io->writeln(' <bg=green;fg=white>          </>');
        $io->newLine();
    }
}