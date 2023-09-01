<?php

declare(strict_types=1);

namespace Mirko\T3maker\Typo3\TCA;

use Mirko\T3maker\Typo3\TCA\Config\RenderType\ConfigRenderTypeInterface;
use Mirko\T3maker\Typo3\TCA\Config\Type\ConfigTypeInterface;
use ReflectionNamedType;
use Traversable;

class TCAConfigProvider
{
    /**
     * @var array<ConfigTypeInterface>
     */
    private array $configTypes;

    /**
     * @var array<ConfigRenderTypeInterface>
     */
    private array $configRenderTypes;

    public function __construct(iterable $configTypes, iterable $configRenderTypes)
    {
        $this->configTypes = $configTypes instanceof Traversable ? iterator_to_array($configTypes) : $configTypes;
        $this->configRenderTypes = $configRenderTypes instanceof Traversable ? iterator_to_array(
            $configRenderTypes
        ) : $configRenderTypes;
    }

    /**
     * @return array|ConfigTypeInterface[]
     */
    public function getAllAvailableConfigTypes(): array
    {
        return $this->configTypes;
    }

    /**
     * @return array|ConfigRenderTypeInterface[]
     */
    public function getAllAvailableConfigRenderTypes(): array
    {
        return $this->configRenderTypes;
    }

    /**
     * @param ReflectionNamedType $namedType
     * @return array
     */
    public function getAvailableConfigTypesForPropertyType(ReflectionNamedType $namedType): array
    {
        $propertyType = $namedType->getName();
        $typeVariants = [];
        if (class_exists($propertyType)) {
            $propertyType = 'object';
        }

        foreach ($this->configTypes as $configType) {
            if (in_array($propertyType, $configType::getPossiblePropertyTypes(), true)) {
                $typeVariants[] = $configType;
            }
        }
        return $typeVariants;
    }

    /**
     * @param ConfigTypeInterface $configType
     * @return array
     */
    public function getAvailableConfigRenderTypesForConfigType(ConfigTypeInterface $configType): array
    {
        $renderTypeVariants = [];
        foreach ($this->configRenderTypes as $renderType) {
            if (in_array($renderType::class, $configType::getPossibleRenderTypes(), true)) {
                $renderTypeVariants[] = $renderType;
            }
        }

        return $renderTypeVariants;
    }

    /**
     * @param string $typeName
     * @return ConfigTypeInterface
     */
    public function getConfigTypeByName(string $typeName): ConfigTypeInterface
    {
        foreach ($this->configTypes as $configType) {
            if ($configType::getTypeName() === $typeName) {
                return $configType;
            }
        }
    }

    /**
     * @param string $renderTypeName
     * @return ConfigRenderTypeInterface|null
     */
    public function getConfigRenderTypeByName(
        $configRenderTypes,
        string $renderTypeName
    ): ConfigRenderTypeInterface|null {
        foreach ($configRenderTypes as $renderType) {
            if ($renderType::getTypeName() === $renderTypeName) {
                return $renderType;
            }
        }

        return null;
    }
}
