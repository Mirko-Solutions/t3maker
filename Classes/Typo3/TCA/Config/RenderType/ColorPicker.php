<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA\Config\RenderType;

use Symfony\Component\Console\Style\SymfonyStyle;

class ColorPicker implements ConfigRenderTypeInterface
{
    public const NAME = 'colorpicker';

    private array|null $valuePicker;

    public static function getTypeName(): string
    {
        return self::NAME;
    }

    public function askRenderTypeDetails(SymfonyStyle $io): array
    {
        // TODO: Implement askRenderTypeDetails() method.
    }
}