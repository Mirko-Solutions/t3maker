<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA\Config\Type;

use Mirko\T3maker\Typo3\TCA\Config\RenderType\ColorPicker;
use Mirko\T3maker\Typo3\TCA\Config\RenderType\InputDateTime;
use Mirko\T3maker\Typo3\TCA\Config\RenderType\InputDefault;
use Symfony\Component\PropertyInfo\Type;

class Input extends AbstractConfigType
{
    public const NAME = 'input';

    public const POSSIBLE_BUILTIN_TYPES = [
        Type::BUILTIN_TYPE_STRING
    ];
    public const POSSIBLE_RENDER_TYPES = [
        ColorPicker::NAME,
        InputDefault::NAME,
        InputDateTime::NAME,
    ];
}