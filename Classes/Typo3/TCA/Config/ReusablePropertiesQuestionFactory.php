<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA\Config;

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

    private const PROPERTY_QUESTION = [
        self::CONFIG_PROPERTY_AUTOCOMPLETE => 'createQuestionForAutocompleteProperty',
        self::CONFIG_PROPERTY_DEFAULT => 'createQuestionForDefaultProperty',
        self::CONFIG_PROPERTY_EVAL => 'createQuestionForEvalProperty',
        self::CONFIG_PROPERTY_READ_ONLY => 'createQuestionForReadOnlyProperty',
        self::CONFIG_PROPERTY_VALUE_PICKER => 'createQuestionForValuePickerProperty',
        self::CONFIG_PROPERTY_SIZE => 'createQuestionForSizeProperty',
    ];

    /**
     * @param string $property
     * @param SymfonyStyle $io
     * @return Question
     */
    public function getQuestionForProperty(string $property, SymfonyStyle $io): Question
    {
        if (!array_key_exists($property, self::PROPERTY_QUESTION)) {
            throw new \RuntimeException("question configuration not found for property {$property}");
        }

        $method = self::PROPERTY_QUESTION[$property];

        if (!method_exists($this, $method)) {
            throw new \RuntimeException("creation method no found for property {$property}");
        }

        return $this->{$method}($io);
    }

    /**
     * @param SymfonyStyle $io
     * @return mixed
     */
    private function createQuestionForSizeProperty(SymfonyStyle $io): mixed
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
     * @return Question
     */
    private function createQuestionForReadOnlyProperty(SymfonyStyle $io): Question
    {
        return $io->askQuestion($this->createBoolQuestion("Select value for " . self::CONFIG_PROPERTY_READ_ONLY));
    }

    /**
     * @param SymfonyStyle $io
     * @return Question
     */
    private function createQuestionForEvalProperty(SymfonyStyle $io): Question
    {

    }

    /**
     * @param SymfonyStyle $io
     * @return Question
     */
    private function createQuestionForDefaultProperty(SymfonyStyle $io): Question
    {
        $question = new Question('Please enter default value');

        $question->setNormalizer(
            function ($value) {
                return (string)$value;
            }
        );

        return $io->askQuestion($question);
    }

    /**
     * @param SymfonyStyle $io
     * @return Question
     */
    private function createQuestionForAutocompleteProperty(SymfonyStyle $io): Question
    {
        return $io->askQuestion($this->createBoolQuestion("Select value for " . self::CONFIG_PROPERTY_AUTOCOMPLETE));
    }

    /**
     * @param SymfonyStyle $io
     * @return Question
     */
    private function createQuestionForValuePickerProperty(SymfonyStyle $io): Question
    {

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
}