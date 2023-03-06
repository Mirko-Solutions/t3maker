<?php

namespace Mirko\T3maker\Maker;

use Mirko\T3maker\Generator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

interface MakerInterface
{
    public function generate(InputInterface $input, SymfonyStyle $io, Generator $generator): void;
}