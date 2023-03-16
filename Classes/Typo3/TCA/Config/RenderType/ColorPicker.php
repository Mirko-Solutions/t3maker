<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA\Config\RenderType;

use Mirko\T3maker\Typo3\TCA\Config\ReusablePropertiesQuestionFactory;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class ColorPicker extends AbstractConfigRenderType
{
    public const NAME = 'colorpicker';

    private array $availableConfigProperties = [
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_SIZE,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_READ_ONLY,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_VALUE_PICKER
    ];

    public static function getTypeName(): string
    {
        return self::NAME;
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