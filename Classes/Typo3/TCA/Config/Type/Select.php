<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA\Config\Type;

class Select  implements ConfigTypeInterface
{
    public const NAME = 'select';

    public const POSSIBLE_BUILTIN_TYPES = [];

    public const POSSIBLE_RENDER_TYPES = [];

    public static function getPossiblePropertyTypes(): array
    {
        return self::POSSIBLE_BUILTIN_TYPES;
    }

    public static function getTypeName(): string
    {
        return self::NAME;
    }

    public static function getPossibleRenderTypes(): array
    {
        return self::POSSIBLE_RENDER_TYPES;
    }
}