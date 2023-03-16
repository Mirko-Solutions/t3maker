<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA\Config\RenderType;

use Mirko\T3maker\Typo3\TCA\Config\ReusablePropertiesQuestionFactory;

class ColorPicker extends AbstractConfigRenderType
{
    public const NAME = 'colorpicker';

    protected array $availableConfigProperties = [
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_SIZE,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_READ_ONLY,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_VALUE_PICKER
    ];
}