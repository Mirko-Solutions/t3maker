<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA\Config\RenderType;

use Symfony\Component\Console\Style\SymfonyStyle;

class InputDateTime implements ConfigRenderTypeInterface
{
    public const NAME = 'inputDateTime';

    public static function getTypeName(): string
    {
        return self::NAME;
    }

    public function askRenderTypeDetails(SymfonyStyle $io): array
    {
        // TODO: Implement askRenderTypeDetails() method.
    }
}