<?php

declare(strict_types=1);

namespace Mirko\T3maker\Typo3\TCA\Config\RenderType\Input;

use Mirko\T3maker\Typo3\TCA\Config\RenderType\AbstractConfigRenderType;
use Mirko\T3maker\Typo3\TCA\Config\ReusablePropertiesQuestionFactory;

class InputDateTime extends AbstractConfigRenderType
{
    public const NAME = 'inputDateTime';

    /**
     * @var array
     */
    protected array $availableConfigProperties = [
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_READ_ONLY,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_PLACEHOLDER,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_DEFAULT,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_EVAL,
    ];

    /**
     * @var array|array[]
     */
    protected array $requiredConfigProperties = [
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_EVAL => [
            'date', 'datetime', 'time', 'timesec',
        ],
    ];
}
