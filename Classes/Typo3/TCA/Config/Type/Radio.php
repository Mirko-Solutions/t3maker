<?php

declare(strict_types=1);

namespace Mirko\T3maker\Typo3\TCA\Config\Type;

use Mirko\T3maker\Typo3\TCA\Config\RenderType\RadioDefault;
use Symfony\Component\PropertyInfo\Type;

class Radio extends AbstractConfigType
{
    public const NAME = 'radio';

    public const POSSIBLE_BUILTIN_TYPES = [
        Type::BUILTIN_TYPE_STRING,
        Type::BUILTIN_TYPE_INT,
    ];

    public const POSSIBLE_RENDER_TYPES = [RadioDefault::class];
}
