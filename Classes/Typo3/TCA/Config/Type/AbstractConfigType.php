<?php

declare(strict_types=1);

namespace Mirko\T3maker\Typo3\TCA\Config\Type;

use Symfony\Component\PropertyInfo\Type;

abstract class AbstractConfigType implements ConfigTypeInterface
{
    public const POSSIBLE_BUILTIN_TYPES = [
        Type::BUILTIN_TYPE_INT,
        Type::BUILTIN_TYPE_FLOAT,
        Type::BUILTIN_TYPE_STRING,
        Type::BUILTIN_TYPE_BOOL,
        Type::BUILTIN_TYPE_RESOURCE,
        Type::BUILTIN_TYPE_OBJECT,
        Type::BUILTIN_TYPE_ARRAY,
        Type::BUILTIN_TYPE_CALLABLE,
        Type::BUILTIN_TYPE_FALSE,
        Type::BUILTIN_TYPE_TRUE,
        Type::BUILTIN_TYPE_NULL,
        Type::BUILTIN_TYPE_ITERABLE,
    ];

    public static function getPossiblePropertyTypes(): array
    {
        return static::POSSIBLE_BUILTIN_TYPES;
    }

    public static function getTypeName(): string
    {
        return static::NAME;
    }

    public static function getPossibleRenderTypes(): array
    {
        return static::POSSIBLE_RENDER_TYPES;
    }
}
