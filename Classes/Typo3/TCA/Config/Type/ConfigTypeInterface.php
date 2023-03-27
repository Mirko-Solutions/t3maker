<?php

namespace Mirko\T3maker\Typo3\TCA\Config\Type;

interface ConfigTypeInterface
{
    public static function getPossiblePropertyTypes(): array;

    /**
     * @return array[RenderType::class]
     */
    public static function getPossibleRenderTypes(): array;

    public static function getTypeName(): string;
}