<?php

declare(strict_types=1);

namespace Mirko\T3maker\Typo3\TCA;

use Mirko\T3maker\Typo3\TCA\Config\RenderType\ConfigRenderType;
use Mirko\T3maker\Typo3\TCA\Config\Type\ConfigType;

class Config
{

    private ConfigType $type;

    private ConfigRenderType $renderType;

    private int $readOnly = 0;

    private int $size = 0;

    private array $renderTypeConfig = [];
}