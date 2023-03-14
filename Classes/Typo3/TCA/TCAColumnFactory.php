<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA;

use Symfony\Component\Console\Style\SymfonyStyle;

class TCAColumnFactory
{
    public function createColumnConfigForTableColumn(\ReflectionProperty $property, SymfonyStyle $io): array
    {
        return [];
    }
}