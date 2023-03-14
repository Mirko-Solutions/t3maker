<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA\Config\Type;

use Mirko\T3maker\Typo3\TCA\Config\RenderType\ColorPicker;

class Input  implements ConfigType
{
    public const POSSIBLE_RENDER_TYPES = [
        ColorPicker::NAME => ColorPicker::class
    ];
}