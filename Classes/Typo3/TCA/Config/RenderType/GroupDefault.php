<?php

declare(strict_types=1);

namespace Mirko\T3maker\Typo3\TCA\Config\RenderType;

use Mirko\T3maker\Typo3\TCA\Config\ReusablePropertiesQuestionFactory;

class GroupDefault extends AbstractConfigRenderType implements DefaultRenderTypeInterface
{
    protected array $availableConfigProperties = [
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_DEFAULT,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_SIZE,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_MAX_ITEMS,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_MIN_ITEMS,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_READ_ONLY,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_ALLOWED,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_DISALLOWED,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_FOREIGN_TABLE,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_MULTIPLE,
    ];

    /**
     * @var array|array[]
     */
    protected array $requiredConfigProperties = [];
}
