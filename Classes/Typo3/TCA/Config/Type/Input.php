<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA\Config\Type;

use Mirko\T3maker\Typo3\TCA\Config\RenderType\Input\ColorPicker;
use Mirko\T3maker\Typo3\TCA\Config\RenderType\Input\InputDateTime;
use Mirko\T3maker\Typo3\TCA\Config\RenderType\Input\InputDefault;
use Mirko\T3maker\Typo3\TCA\Config\RenderType\Input\InputLink;
use Symfony\Component\PropertyInfo\Type;

class Input extends AbstractConfigType
{
    public const NAME = 'input';

    public const POSSIBLE_BUILTIN_TYPES = [
        Type::BUILTIN_TYPE_STRING,
        Type::BUILTIN_TYPE_INT,
    ];
    public const POSSIBLE_RENDER_TYPES = [
        ColorPicker::class,
        InputDefault::class,
        InputDateTime::class,
        InputLink::class,
    ];
}