<?php

declare(strict_types=1);

namespace Mirko\T3maker\Typo3\TCA\Config\Type;

use Mirko\T3maker\Typo3\TCA\Config\RenderType\Select\SelectMultipleSideBySide;
use Mirko\T3maker\Typo3\TCA\Config\RenderType\Select\SelectSingle;
use Mirko\T3maker\Typo3\TCA\Config\RenderType\Select\SelectSingleBox;
use Mirko\T3maker\Typo3\TCA\Config\RenderType\Select\SelectTree;
use Symfony\Component\PropertyInfo\Type;

class Select extends AbstractConfigType
{
    public const NAME = 'select';

    public const POSSIBLE_BUILTIN_TYPES = [
        Type::BUILTIN_TYPE_STRING,
        Type::BUILTIN_TYPE_INT,
    ];

    public const POSSIBLE_RENDER_TYPES = [
        SelectSingle::class,
        SelectSingleBox::class,
        SelectMultipleSideBySide::class,
        SelectTree::class,
    ];
}
