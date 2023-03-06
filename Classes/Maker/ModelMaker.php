<?php

declare(strict_types=1);

namespace Mirko\T3maker\Maker;

use JetBrains\PhpStorm\NoReturn;
use Mirko\T3maker\Generator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ModelMaker extends AbstractMaker
{
    #[NoReturn] public function generate(InputInterface $input, SymfonyStyle $io, Generator $generator): void
    {
        $extensionName = $input->getArgument('extensionName');

        $entityClassDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Model\\'
        );

        dd($entityClassDetails);
    }
}