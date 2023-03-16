<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA\Config\Type;

abstract class AbstractConfigType implements ConfigTypeInterface
{
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