<?php

declare(strict_types=1);


namespace Mirko\T3maker\Typo3\TCA\Config;

use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ReusablePropertiesQuestionFactory
{
    public const CONFIG_PROPERTY_AUTOCOMPLETE = 'autocomplete';
    public const CONFIG_PROPERTY_DEFAULT = 'default';
    public const CONFIG_PROPERTY_EVAL = 'eval';
    public const CONFIG_PROPERTY_READ_ONLY = 'readOnly';
    public const CONFIG_PROPERTY_SIZE = 'size';
    public const CONFIG_PROPERTY_VALUE_PICKER = 'valuePicker';
    public const CONFIG_PROPERTY_ITEMS = 'items';
    public const CONFIG_PROPERTY_PLACEHOLDER = 'placeholder';
    public const CONFIG_PROPERTY_FOREIGN_TABLE = 'foreign_table';
    public const CONFIG_PROPERTY_FOREIGN_TABLE_WHERE = 'foreign_table_where';
    public const CONFIG_PROPERTY_MIN_ITEMS = 'minitems';
    public const CONFIG_PROPERTY_MAX_ITEMS = 'maxitems';

    private string $property = '';

    private const PROPERTY_QUESTION = [
        self::CONFIG_PROPERTY_AUTOCOMPLETE => 'askQuestionForAutocompleteProperty',
        self::CONFIG_PROPERTY_DEFAULT => 'askQuestionForDefaultProperty',
        self::CONFIG_PROPERTY_EVAL => 'askQuestionForEvalProperty',
        self::CONFIG_PROPERTY_READ_ONLY => 'askQuestionForReadOnlyProperty',
        self::CONFIG_PROPERTY_VALUE_PICKER => 'askQuestionForValuePickerProperty',
        self::CONFIG_PROPERTY_SIZE => 'askQuestionForSizeProperty',
        self::CONFIG_PROPERTY_PLACEHOLDER => 'askQuestionForPlaceholderProperty',
        self::CONFIG_PROPERTY_ITEMS => 'askQuestionForItemsProperty',
        self::CONFIG_PROPERTY_FOREIGN_TABLE => 'askQuestionForForeignTableProperty',
        self::CONFIG_PROPERTY_FOREIGN_TABLE_WHERE => 'askQuestionForForeignTableWhereProperty',
        self::CONFIG_PROPERTY_MIN_ITEMS => 'askQuestionForMinItemsProperty',
        self::CONFIG_PROPERTY_MAX_ITEMS => 'askQuestionForMaxItemsProperty',
    ];

    /**
     * @param string $property
     * @param SymfonyStyle $io
     * @param array $additionalArg
     * @return mixed
     */
    public function askQuestionForProperty(string $property, SymfonyStyle $io, array $additionalArg = []): mixed
    {
        if (!array_key_exists($property, self::PROPERTY_QUESTION)) {
            throw new \RuntimeException("question configuration not found for property {$property}");
        }

        $method = self::PROPERTY_QUESTION[$property];

        if (!method_exists($this, $method)) {
            throw new \RuntimeException("creation method no found for property {$property}");
        }

        $this->property = $property;

        return $this->{$method}($io, $additionalArg);
    }

    /**
     * @param SymfonyStyle $io
     * @return int
     */
    private function askQuestionForSizeProperty(SymfonyStyle $io, array $additionalArg): int
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
    private function askQuestionForReadOnlyProperty(SymfonyStyle $io, array $additionalArg): mixed
    {
        return $io->askQuestion($this->createBoolQuestion("Select value for " . self::CONFIG_PROPERTY_READ_ONLY));
    }

    /**
     * @param SymfonyStyle $io
     * @param mixed ...$arg
     * @return string
     */
    private function askQuestionForEvalProperty(SymfonyStyle $io, array $additionalArg): string
    {
        $question = $this->createTextQuestion('Please enter eval field value');

        if (isset($additionalArg['validation']) && is_array($additionalArg['validation'])) {
            $validation = $additionalArg['validation'];
            $question->setValidator(
                function ($value) use ($validation) {
                    if ($value === null) {
                        return '';
                    }

                    $evalParams = explode(',', $value);
                    foreach ($validation as $item) {
                        if (in_array($item, $evalParams, true)) {
                            return $value;
                        }
                    }

                    $values = implode(',', $validation);
                    throw new \RuntimeException(
                        "for selected field needs '{$this->property}' set to either to {$values}"
                    );
                }
            );
        }

        return $io->askQuestion($question);
    }

    /**
     * @param SymfonyStyle $io
     * @param mixed ...$arg
     * @return mixed
     */
    private function askQuestionForPlaceholderProperty(SymfonyStyle $io, array $additionalArg): string
    {
        $question = $this->createTextQuestion('Please enter placeholder value');

        return $io->askQuestion($question);
    }

    /**
     * @param SymfonyStyle $io
     * @return string
     */
    private function askQuestionForDefaultProperty(SymfonyStyle $io, array $additionalArg): string
    {
        $question = $this->createTextQuestion('Please enter default value');

        return $io->askQuestion($question);
    }

    /**
     * @param SymfonyStyle $io
     * @return mixed
     */
    private function askQuestionForAutocompleteProperty(SymfonyStyle $io, array $additionalArg): mixed
    {
        return $io->askQuestion($this->createBoolQuestion("Select value for " . self::CONFIG_PROPERTY_AUTOCOMPLETE));
    }

    /**
     * @param SymfonyStyle $io
     * @param array $additionalArg
     * @return array
     */
    private function askQuestionForValuePickerProperty(SymfonyStyle $io, array $additionalArg): array
    {
        return ['items' => $this->itemsQuestion($io)];
    }

    private function itemsQuestion(SymfonyStyle $io): array
    {
        $items = [];
        while (true) {
            $itemKey = $io->ask(
                'Enter item key (press <return> to stop)',
                null,
                function ($name) use ($items) {
                    // allow it to be empty
                    if (!$name) {
                        return $name;
                    }

                    if (\in_array($name, $items, true)) {
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

            $items[] = [$itemValue, $itemKey];
        }

        return $items;
    }

    private function askQuestionForItemsProperty(SymfonyStyle $io, array $additionalArg): array
    {
        return $this->itemsQuestion($io);
    }

    private function askQuestionForForeignTableProperty(SymfonyStyle $io, array $additionalArg): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class);
        $tables = $queryBuilder->getConnectionForTable('pages')->getSchemaManager()->listTables();

        $tableNames = [];

        foreach ($tables as $table) {
            $tableNames[] = $table->getName();
        }

        $question = $this->createTextQuestion('Please enter value for foreign_table', $tableNames);

        return $io->askQuestion($question);
    }

    private function askQuestionForForeignTableWhereProperty(SymfonyStyle $io, array $additionalArg): string
    {
        $question = $this->createTextQuestion('Please enter value for foreign_table_where');

        return $io->askQuestion($question);
    }

    private function askQuestionForMinItemsProperty(SymfonyStyle $io, array $additionalArg): int
    {
        $question = $this->createNumberQuestion('Please enter value for min_items');

        return $io->askQuestion($question);
    }
    private function askQuestionForMaxItemsProperty(SymfonyStyle $io, array $additionalArg): int
    {
        $question = $this->createNumberQuestion('Please enter value for max_items');

        return $io->askQuestion($question);
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
    private function createTextQuestion($message, iterable $autocompleterValues = null): Question
    {
        $question = new Question($message);
        $question->setAutocompleterValues($autocompleterValues);
        $question->setNormalizer(
            function ($value) {
                return (string)$value;
            }
        );

        return $question;
    }

    /**
     * @param $message
     * @return Question
     */
    private function createNumberQuestion($message): Question
    {
        $question = new Question($message);

        $question->setValidator(
            static function ($value) {
                if (preg_match('/^\d+$/', $value)) {
                   return $value;
                }

                throw new \RuntimeException('Only number allowed');
        });

        $question->setNormalizer(
            function ($value) {
                return (int)$value;
            }
        );

        return $question;
    }
}