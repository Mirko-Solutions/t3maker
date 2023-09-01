<?php

declare(strict_types=1);

namespace Mirko\T3maker\Typo3\TCA\Config;

use InvalidArgumentException;
use Mirko\T3maker\Utility\StringUtility;
use RuntimeException;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
    public const CONFIG_PROPERTY_FOREIGN_FIELD = 'foreign_field';
    public const CONFIG_PROPERTY_FOREIGN_TABLE_FIELD = 'foreign_table_field';
    public const CONFIG_PROPERTY_FOREIGN_TABLE_WHERE = 'foreign_table_where';
    public const CONFIG_PROPERTY_MIN_ITEMS = 'minitems';
    public const CONFIG_PROPERTY_MAX_ITEMS = 'maxitems';
    public const CONFIG_PROPERTY_DS = 'ds';
    public const CONFIG_PROPERTY_BEHAVIOUR = 'behaviour';
    public const CONFIG_PROPERTY_DS_POINTER_FIELD = 'ds_pointerField';
    public const CONFIG_PROPERTY_DS_POINTER_FIELD_SEARCH_PARENT = 'ds_pointerField_searchParent';
    public const CONFIG_PROPERTY_DS_POINTER_FIELD_SEARCH_PARENT_SUB_FIELD = 'ds_pointerField_searchParent_subField';
    public const CONFIG_PROPERTY_DS_TABLE_FIELD = 'ds_tableField';
    public const CONFIG_PROPERTY_ALLOWED = 'allowed';
    public const CONFIG_PROPERTY_DISALLOWED = 'disallowed';
    public const CONFIG_PROPERTY_MULTIPLE = 'multiple';

    private string $property = '';

    /**
     * @param string       $property
     * @param SymfonyStyle $io
     * @param array        $additionalArg
     *
     * @return mixed
     *
     * @throws RuntimeException
     */
    public function askQuestionForProperty(string $property, SymfonyStyle $io, array $additionalArg = []): mixed
    {
        $this->property = $property;

        $propertyCarmelCase = StringUtility::asCamelCase($property);

        $method = 'askQuestionFor' . $propertyCarmelCase . 'Property';

        if (!method_exists($this, $method)) {
            throw new RuntimeException('creation method no found for property ' . $property);
        }

        return $this->{$method}($io, $additionalArg);
    }

    /**
     * @param SymfonyStyle $io
     *
     * @return int
     */
    private function askQuestionForSizeProperty(SymfonyStyle $io, array $additionalArg): int
    {
        $question = $this->createNumberQuestion();

        $question->setNormalizer(fn ($value) => (int)$value);
        $question->setValidator(
            function ($value) {
                if (trim($value) === '') {
                    throw new RuntimeException('The size cannot be empty');
                }

                if (!is_int($value)) {
                    throw new RuntimeException('The size must be int');
                }

                return (int)$value;
            }
        );

        return $io->askQuestion($question);
    }

    /**
     * @param SymfonyStyle $io
     *
     * @return string
     */
    private function askQuestionForReadOnlyProperty(SymfonyStyle $io, array $additionalArg): mixed
    {
        return $io->askQuestion($this->createBoolQuestion());
    }

    /**
     * @param SymfonyStyle $io
     * @param mixed        ...$arg
     *
     * @return string
     */
    private function askQuestionForEvalProperty(SymfonyStyle $io, array $additionalArg): string
    {
        $question = $this->createTextQuestion();

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
                    throw new RuntimeException(
                        'for selected field needs ' . $this->property . ' set to either to ' . $values
                    );
                }
            );
        }

        return $io->askQuestion($question);
    }

    /**
     * @param SymfonyStyle $io
     * @param mixed        ...$arg
     *
     * @return mixed
     */
    private function askQuestionForPlaceholderProperty(SymfonyStyle $io, array $additionalArg): string
    {
        $question = $this->createTextQuestion();

        return $io->askQuestion($question);
    }

    /**
     * @param SymfonyStyle $io
     *
     * @return string
     */
    private function askQuestionForDefaultProperty(SymfonyStyle $io, array $additionalArg): string
    {
        $question = $this->createTextQuestion();

        return $io->askQuestion($question);
    }

    /**
     * @param SymfonyStyle $io
     *
     * @return mixed
     */
    private function askQuestionForAutocompleteProperty(SymfonyStyle $io, array $additionalArg): mixed
    {
        return $io->askQuestion($this->createBoolQuestion());
    }

    /**
     * @param SymfonyStyle $io
     * @param array        $additionalArg
     *
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
                        throw new InvalidArgumentException(sprintf('The "%s" key already exists.', $name));
                    }

                    return $name;
                }
            );

            if (!$itemKey) {
                break;
            }

            $itemValue = $io->ask(
                'Enter value for ' . $itemKey,
                null,
                fn ($name) => $name
            );

            $items[] = [$itemValue, $itemKey];
        }

        return $items;
    }

    private function askQuestionForItemsProperty(SymfonyStyle $io, array $additionalArg): array
    {
        return $this->itemsQuestion($io);
    }

    private function askQuestionForDSProperty(SymfonyStyle $io, array $additionalArg): array
    {
        return $this->itemsQuestion($io);
    }

    private function askQuestionForBehaviourProperty(SymfonyStyle $io, array $additionalArg): array
    {
        return $this->itemsQuestion($io);
    }

    private function askQuestionForDSTableFieldProperty(SymfonyStyle $io, array $additionalArg): string
    {
        $question = $this->createTextQuestion();

        return $io->askQuestion($question);
    }

    private function askQuestionForDSPointerFieldProperty(SymfonyStyle $io, array $additionalArg): string
    {
        $question = $this->createTextQuestion();

        return $io->askQuestion($question);
    }

    private function askQuestionForDSPointerFieldSearchParentProperty(SymfonyStyle $io, array $additionalArg): string
    {
        $question = $this->createTextQuestion();

        return $io->askQuestion($question);
    }

    private function askQuestionForDSPointerFieldSearchParentSubFieldProperty(
        SymfonyStyle $io,
        array $additionalArg
    ): string {
        $question = $this->createTextQuestion();

        return $io->askQuestion($question);
    }

    private function askQuestionForForeignTableProperty(SymfonyStyle $io, array $additionalArg): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class);
        $tables = $queryBuilder->getConnectionForTable('pages')->getSchemaManager()->listTables();

        $tableNames = [];

        foreach ($tables as $table) {
            $tableNames[] = $table->getName();
        }

        $question = $this->createTextQuestion(autocompleterValues: $tableNames);

        return $io->askQuestion($question);
    }

    private function askQuestionForForeignFieldProperty(SymfonyStyle $io, array $additionalArg): string
    {
        $question = $this->createTextQuestion();

        return $io->askQuestion($question);
    }

    private function askQuestionForForeignTableFieldProperty(SymfonyStyle $io, array $additionalArg): string
    {
        $question = $this->createTextQuestion();

        return $io->askQuestion($question);
    }

    private function askQuestionForForeignTableWhereProperty(SymfonyStyle $io, array $additionalArg): string
    {
        $question = $this->createTextQuestion();

        return $io->askQuestion($question);
    }

    private function askQuestionForMinItemsProperty(SymfonyStyle $io, array $additionalArg): int
    {
        $question = $this->createNumberQuestion();

        return $io->askQuestion($question);
    }

    private function askQuestionForMaxItemsProperty(SymfonyStyle $io, array $additionalArg): int
    {
        $question = $this->createNumberQuestion();

        return $io->askQuestion($question);
    }

    private function askQuestionForAllowedProperty(SymfonyStyle $io, array $additionalArg): int
    {
        $question = $this->createTextQuestion();

        return $io->askQuestion($question);
    }

    private function askQuestionForDisallowedProperty(SymfonyStyle $io, array $additionalArg): int
    {
        $question = $this->createTextQuestion();

        return $io->askQuestion($question);
    }

    private function askQuestionForMultipleProperty(SymfonyStyle $io, array $additionalArg): int
    {
        $question = $this->createBoolQuestion();

        return $io->askQuestion($question);
    }

    /**
     * @param $message
     *
     * @return ChoiceQuestion
     */
    private function createBoolQuestion($message = null): ChoiceQuestion
    {
        $choices = ['0', '1'];

        if ($message === null) {
            $message = 'Select value for' . $this->property;
        }

        $question = new ChoiceQuestion(
            $message,
            $choices,
            0
        );

        $question->setNormalizer(
            static fn ($value) => array_key_exists($value, $choices) ? $choices[$value] : $value
        );

        return $question;
    }

    /**
     * @param $message
     *
     * @return Question
     */
    private function createTextQuestion($message = null, iterable $autocompleterValues = null): Question
    {
        if ($message === null) {
            $message = 'Please enter value for ' . $this->property;
        }

        $question = new Question($message);
        $question->setAutocompleterValues($autocompleterValues);
        $question->setNormalizer(
            fn ($value) => (string)$value
        );

        return $question;
    }

    /**
     * @param $message
     *
     * @return Question
     */
    private function createNumberQuestion($message = null): Question
    {
        if ($message === null) {
            $message = 'Please enter value for ' . $this->property;
        }

        $question = new Question($message);

        $question->setValidator(
            static function ($value) {
                if (preg_match('/^\d+$/', (string)$value)) {
                    return $value;
                }

                throw new RuntimeException('Only number allowed');
            }
        );

        $question->setNormalizer(fn ($value) => (int)$value);

        return $question;
    }
}
