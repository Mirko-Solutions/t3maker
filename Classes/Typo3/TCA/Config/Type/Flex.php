<?php

declare(strict_types=1);

namespace Mirko\T3maker\Typo3\TCA\Config\Type;

use Mirko\T3maker\Typo3\TCA\Config\RenderType\FlexDefault;
use Symfony\Component\PropertyInfo\Type;

class Flex extends AbstractConfigType
{
    public const NAME = 'flex';

    public const POSSIBLE_BUILTIN_TYPES = [Type::BUILTIN_TYPE_STRING];

    public const POSSIBLE_RENDER_TYPES = [FlexDefault::class];
}
