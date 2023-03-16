<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA\Config\RenderType;

use Mirko\T3maker\Typo3\TCA\Config\ReusablePropertiesQuestionFactory;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractConfigRenderType implements ConfigRenderTypeInterface
{
    protected array $availableConfigProperties = [];

    public static function getTypeName(): string
    {
        return static::NAME;
    }

    public function __construct(private ReusablePropertiesQuestionFactory $propertiesQuestionFactory)
    {
    }

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

            $propertyQuestion = $this->propertiesQuestionFactory->getQuestionForProperty($property, $io);
            $propertiesConfiguration[$property] = $propertyQuestion;
        }

        return $propertiesConfiguration;
    }

    public function askRenderTypeDetails(SymfonyStyle $io): array
    {
        $question = new ConfirmationQuestion('Do you want to add additional Properties?', false);

        $additionalProperties = $io->askQuestion($question);

        if ($additionalProperties === false) {
            return [];
        }

        return $this->askForAdditionalProperties($io, $this->availableConfigProperties);
    }
}