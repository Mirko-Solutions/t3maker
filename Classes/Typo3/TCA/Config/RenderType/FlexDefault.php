<?php

declare(strict_types=1);

namespace Mirko\T3maker\Typo3\TCA\Config\RenderType;

use Mirko\T3maker\Typo3\TCA\Config\ReusablePropertiesQuestionFactory;

class FlexDefault extends AbstractConfigRenderType implements DefaultRenderTypeInterface
{
    protected array $availableConfigProperties = [
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_DS,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_DS_POINTER_FIELD,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_DS_POINTER_FIELD_SEARCH_PARENT,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_DS_POINTER_FIELD_SEARCH_PARENT_SUB_FIELD,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_DS_TABLE_FIELD,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_BEHAVIOUR,
    ];

    /**
     * @var array|array[]
     */
    protected array $requiredConfigProperties = [
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_DS => [],
    ];
}
