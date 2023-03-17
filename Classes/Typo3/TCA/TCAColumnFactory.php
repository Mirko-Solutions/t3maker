<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA;

use Mirko\T3maker\Parser\ModelParser;
use Mirko\T3maker\Typo3\TCA\Config\RenderType\ConfigRenderTypeInterface;
use Mirko\T3maker\Typo3\TCA\Config\RenderType\DefaultRenderTypeInterface;
use Mirko\T3maker\Typo3\TCA\Config\Type\ConfigTypeInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class TCAColumnFactory
{
    private ?SymfonyStyle $io = null;

    public function __construct(private TCAConfigProvider $TCAConfigProvider)
    {
    }

    public function createColumnConfigForTableColumn(\ReflectionProperty $property, SymfonyStyle $io): array
    {
        $this->io = $io;

        $propertyTypes = ModelParser::getPropertyType($property);

        $configType = $this->askConfigurationForPropertyType($propertyTypes);

        $config = new Config($configType);

        $renderType = $this->askConfigurationForRenderType($configType);

        if ($renderType) {
            $config->setRenderType($renderType);
            $renderTypeConfig = $renderType->askRenderTypeDetails($io);
            $config->setRenderTypeConfig($renderTypeConfig);
        }

        if ($renderType instanceof DefaultRenderTypeInterface) {
            $config->setRenderType(null);
        }

        return $config->__toArray();
    }

    /**
     * @param array<\ReflectionNamedType> $propertyTypes
     * @return ConfigTypeInterface
     */
    private function askConfigurationForPropertyType(array $propertyTypes): ConfigTypeInterface
    {
        $typeVariants = [];
        $message = 'available config types, please select one';
        if (empty($propertyTypes)) {
            $typeVariants = $this->TCAConfigProvider->getAllAvailableConfigTypes();
        } else {
            $propertyTypeNames = '';
            foreach ($propertyTypes as $propertyType) {
                $propertyTypeNames .= $propertyType->getName() . ', ';
                $typeVariants = array_merge(
                    $this->TCAConfigProvider->getAvailableConfigTypesForPropertyType($propertyType),
                    $typeVariants
                );
            }
            $message = "available config types for property with builtin type {$propertyTypeNames} please select one";
        }

        $choices = [];

        foreach ($typeVariants as $typeVariant) {
            $choices[] = $typeVariant::getTypeName();
        }

        $choices = array_unique($choices);

        $question = new ChoiceQuestion(
            $message,
            $choices,
            null
        );

        $question->setNormalizer(
            function ($value) use ($choices) {
                if ($value === null) {
                    return null;
                }
                return array_key_exists($value, $choices) ? $choices[$value] : $value;
            }
        );

        return $this->TCAConfigProvider->getConfigTypeByName($this->io->askQuestion($question));
    }

    private function askConfigurationForRenderType(ConfigTypeInterface $configType): ConfigRenderTypeInterface|null
    {
        $renderTypes = $this->TCAConfigProvider->getAvailableConfigRenderTypesForConfigType($configType);

        $choices = [];

        foreach ($renderTypes as $renderType) {
            $choices[] = $renderType::getTypeName();
        }

        $question = new ChoiceQuestion(
            "available config render types for config type '{$configType->getTypeName()}', please select one",
            $choices,
            null
        );

        $question->setNormalizer(
            function ($value) use ($choices) {
                if ($value === null) {
                    return null;
                }
                return array_key_exists($value, $choices) ? $choices[$value] : $value;
            }
        );

        return $this->TCAConfigProvider->getConfigRenderTypeByName($renderTypes, $this->io->askQuestion($question));
    }
}