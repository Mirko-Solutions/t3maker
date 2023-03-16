<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA\Config;

use Mirko\T3maker\Validator\ClassValidator;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReusablePropertiesQuestionFactory
{
    public const CONFIG_PROPERTY_AUTOCOMPLETE = 'autocomplete';
    public const CONFIG_PROPERTY_DEFAULT = 'default';
    public const CONFIG_PROPERTY_EVAL = 'eval';
    public const CONFIG_PROPERTY_READ_ONLY = 'readOnly';
    public const CONFIG_PROPERTY_SIZE = 'size';
    public const CONFIG_PROPERTY_VALUE_PICKER = 'valuePicker';

    public const CONFIG_PROPERTY_PLACEHOLDER = 'placeholder';

    private string $property = '';

    private const PROPERTY_QUESTION = [
        self::CONFIG_PROPERTY_AUTOCOMPLETE => 'askQuestionForAutocompleteProperty',
        self::CONFIG_PROPERTY_DEFAULT => 'askQuestionForDefaultProperty',
        self::CONFIG_PROPERTY_EVAL => 'askQuestionForEvalProperty',
        self::CONFIG_PROPERTY_READ_ONLY => 'askQuestionForReadOnlyProperty',
        self::CONFIG_PROPERTY_VALUE_PICKER => 'askQuestionForValuePickerProperty',
        self::CONFIG_PROPERTY_SIZE => 'askQuestionForSizeProperty',
        self::CONFIG_PROPERTY_PLACEHOLDER => 'askQuestionForPlaceholderProperty',
    ];

    /**
     * @param string $property
     * @param SymfonyStyle $io
     * @return mixed
     */
    public function getQuestionForProperty(string $property, SymfonyStyle $io): mixed
    {
        if (!array_key_exists($property, self::PROPERTY_QUESTION)) {
            throw new \RuntimeException("question configuration not found for property {$property}");
        }

        $method = self::PROPERTY_QUESTION[$property];

        if (!method_exists($this, $method)) {
            throw new \RuntimeException("creation method no found for property {$property}");
        }

        $this->property = $property;

        return $this->{$method}($io);
    }

    /**
     * @param SymfonyStyle $io
     * @return int
     */
    private function askQuestionForSizeProperty(SymfonyStyle $io): int
    {
        $question = new Question('Please enter size');

        $question->setNormalizer(
            function ($value) {
                return (int)$value;
            }
        );
        $question->setValidator(
            function ($value) {
                if ('' === trim($value)) {
                    throw new \RuntimeException('The size be empty');
                }

                if (!is_int($value)) {
                    throw new \RuntimeException('The size must be int');
                }

                return (int)$value;
            }
        );

        return $io->askQuestion($question);
    }

    /**
     * @param SymfonyStyle $io
     * @return string
     */
    private function askQuestionForReadOnlyProperty(SymfonyStyle $io): mixed
    {
        return $io->askQuestion($this->createBoolQuestion("Select value for " . self::CONFIG_PROPERTY_READ_ONLY));
    }

    /**
     * @param SymfonyStyle $io
     * @return string
     */
    private function askQuestionForEvalProperty(SymfonyStyle $io): string
    {
        $question = $this->createTextQuestion('Please enter eval field value');
        //TODO validator
        return $io->askQuestion($question);
    }

    /**
     * @param SymfonyStyle $io
     * @return mixed
     */
    private function askQuestionForPlaceholderProperty(SymfonyStyle $io): string
    {
        $question = $this->createTextQuestion('Please enter placeholder value');

        return $io->askQuestion($question);
    }

    /**
     * @param SymfonyStyle $io
     * @return string
     */
    private function askQuestionForDefaultProperty(SymfonyStyle $io): string
    {
        $question = $this->createTextQuestion('Please enter default value');

        return $io->askQuestion($question);
    }

    /**
     * @param SymfonyStyle $io
     * @return mixed
     */
    private function askQuestionForAutocompleteProperty(SymfonyStyle $io): mixed
    {
        return $io->askQuestion($this->createBoolQuestion("Select value for " . self::CONFIG_PROPERTY_AUTOCOMPLETE));
    }

    /**
     * @param SymfonyStyle $io
     * @return array
     */
    private function askQuestionForValuePickerProperty(SymfonyStyle $io): array
    {
        $items = ['items' => []];

        while (true) {
            $itemKey = $io->ask(
                'Enter item key (press <return> to stop)',
                null,
                function ($name) use ($items) {
                    // allow it to be empty
                    if (!$name) {
                        return $name;
                    }

                    if (\in_array($name, $items['items'], true)) {
                        throw new \InvalidArgumentException(sprintf('The "%s" key already exists.', $name));
                    }

                    return $name;
                }
            );

            if (!$itemKey) {
                break;
            }

            $itemValue = $io->ask(
                "Enter value for {$itemKey}",
                null,
                function ($name) {
                    return $name;
                }
            );

            $items['items'][] = [$itemValue, $itemKey];
        }

        return $items;
    }

    /**
     * @param $message
     * @return ChoiceQuestion
     */
    private function createBoolQuestion($message): ChoiceQuestion
    {
        $choices = ['0', '1'];

        $question = new ChoiceQuestion(
            $message,
            $choices,
            0
        );

        $question->setNormalizer(
            static function ($value) use ($choices) {
                return array_key_exists($value, $choices) ? $choices[$value] : $value;
            }
        );

        return $question;
    }

    /**
     * @param $message
     * @return Question
     */
    private function createTextQuestion($message): Question
    {
        $question = new Question($message);

        $question->setNormalizer(
            function ($value) {
                return (string)$value;
            }
        );

        return $question;
    }
}