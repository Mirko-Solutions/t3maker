<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA\Config\RenderType\Select;

use Mirko\T3maker\Typo3\TCA\Config\RenderType\AbstractConfigRenderType;
use Mirko\T3maker\Typo3\TCA\Config\ReusablePropertiesQuestionFactory;

class SelectMultipleSideBySide extends AbstractConfigRenderType
{
    public const NAME = 'selectMultipleSideBySide';

    protected array $availableConfigProperties = [
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_SIZE,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_READ_ONLY,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_PLACEHOLDER,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_DEFAULT,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_EVAL,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_ITEMS,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_FOREIGN_TABLE,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_FOREIGN_TABLE_WHERE,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_MAX_ITEMS,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_MIN_ITEMS,
    ];

    /**
     * @var array|array[]
     */
    protected array $requiredConfigProperties = [
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_ITEMS => [],
    ];
}