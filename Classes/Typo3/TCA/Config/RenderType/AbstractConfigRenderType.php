<?php

declare(strict_types=1);

namespace Mirko\T3maker\Typo3\TCA\Config\RenderType;

use Mirko\T3maker\Typo3\TCA\Config\ReusablePropertiesQuestionFactory;
use ReflectionProperty;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractConfigRenderType implements ConfigRenderTypeInterface
{
    /**
     * @var array
     */
    protected array $availableConfigProperties = [];
    /**
     * @var array|array[]
     */
    protected array $requiredConfigProperties = [];

    public static function getTypeName(): string
    {
        return static::NAME;
    }

    public function __construct(private ReusablePropertiesQuestionFactory $propertiesQuestionFactory)
    {
    }

    public function askForConfigPresets(SymfonyStyle $io, ReflectionProperty $property): array
    {
        return [];
    }

    public function getExampleConfig(): array
    {
        return [];
    }

    /**
     * @param SymfonyStyle $io
     * @param array        $propertiesConfig
     *
     * @return array
     */
    protected function askForRequiredProperties(SymfonyStyle $io, array $propertiesConfig = []): array
    {
        $propertiesConfiguration = [];
        $propertiesList = array_keys($propertiesConfig);
        $validator = static function ($value) use ($propertiesList) {
            if (array_key_exists($value, $propertiesList) || in_array($value, $propertiesList, true)) {
                return $value;
            }

            throw new InvalidArgumentException(sprintf('Value "%s" is invalid', $value));
        };

        $normalizer = static fn ($value) => array_key_exists($value, $propertiesList) ? $propertiesList[$value] : $value;

        while (!empty($propertiesList)) {
            $question = new ChoiceQuestion(
                'Choose required property that you want to change',
                $propertiesList,
                array_key_first($propertiesList)
            );

            $question->setValidator($validator);
            $question->setNormalizer($normalizer);
            $property = $io->askQuestion($question);
            $propertyConfig = $this->propertiesQuestionFactory->askQuestionForProperty(
                $property,
                $io,
                ['validation' => $propertiesConfig[$property]]
            );
            $propertiesConfiguration[$property] = $propertyConfig;

            unset($propertiesList[array_search($property, $propertiesList, true)]);
        }

        return $propertiesConfiguration;
    }

    /**
     * @param SymfonyStyle $io
     * @param array        $propertiesList
     *
     * @return array
     */
    protected function askForAdditionalProperties(SymfonyStyle $io, array $propertiesList = []): array
    {
        $propertiesConfiguration = [];

        $validator = static function ($value) use ($propertiesList) {
            if ($value === null) {
                return null;
            }

            if (array_key_exists($value, $propertiesList) || in_array($value, $propertiesList, true)) {
                return $value;
            }

            throw new InvalidArgumentException(sprintf('Value "%s" is invalid', $value));
        };

        $normalizer = static function ($value) use ($propertiesList) {
            if ($value === null) {
                return null;
            }
            return array_key_exists($value, $propertiesList) ? $propertiesList[$value] : $value;
        };

        while (true) {
            $question = new ChoiceQuestion(
                'Choose optional property that you want to add (press <return> to stop)',
                $propertiesList,
                null
            );
            $question->setValidator($validator);
            $question->setNormalizer($normalizer);
            $property = $io->askQuestion($question);

            if ($property === null) {
                break;
            }

            $additionalArguments = [];

            if (array_key_exists($property, $this->requiredConfigProperties)) {
                $additionalArguments = ['validation' => $this->requiredConfigProperties[$property]];
            }

            $propertyConfig = $this->propertiesQuestionFactory->askQuestionForProperty(
                $property,
                $io,
                $additionalArguments
            );

            $propertiesConfiguration[$property] = $propertyConfig;
        }

        return $propertiesConfiguration;
    }

    public function askRenderTypeDetails(SymfonyStyle $io): array
    {
        $config = [];

        if (!empty($this->requiredConfigProperties)) {
            $io->text('Configure required properties');
            $config = $this->askForRequiredProperties($io, $this->requiredConfigProperties);
        }

        $question = new ConfirmationQuestion('Do you want to add additional Properties?', false);

        $additionalProperties = $io->askQuestion($question);

        if ($additionalProperties === false) {
            return $config;
        }

        return array_merge($config, $this->askForAdditionalProperties($io, $this->availableConfigProperties));
    }
}
