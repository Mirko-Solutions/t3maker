<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA\Config\RenderType;

class ColorPicker implements ConfigRenderType
{
    public const NAME = 'colorpicker';

    private array|null $valuePicker;
}