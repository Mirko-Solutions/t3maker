<?php

declare(strict_types=1);

namespace Mirko\T3maker\Typo3\TCA\Config\Type;

use Mirko\T3maker\Typo3\TCA\Config\RenderType\InlineDefault;
use Symfony\Component\PropertyInfo\Type;

class Inline extends AbstractConfigType
{
    public const NAME = 'inline';

    public const POSSIBLE_BUILTIN_TYPES = [
        Type::BUILTIN_TYPE_INT,
        Type::BUILTIN_TYPE_STRING,
        Type::BUILTIN_TYPE_RESOURCE,
        Type::BUILTIN_TYPE_OBJECT,
        Type::BUILTIN_TYPE_ARRAY,
        Type::BUILTIN_TYPE_NULL,
    ];

    public const POSSIBLE_RENDER_TYPES = [InlineDefault::class];
}
