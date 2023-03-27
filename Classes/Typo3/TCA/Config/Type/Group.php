<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA\Config\Type;

use Mirko\T3maker\Typo3\TCA\Config\RenderType\GroupDefault;
use Symfony\Component\PropertyInfo\Type;

class Group extends AbstractConfigType
{
    public const NAME = 'group';
    public const POSSIBLE_RENDER_TYPES = [
        GroupDefault::class
    ];
}