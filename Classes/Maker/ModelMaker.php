<?php

declare(strict_types=1);

namespace Mirko\T3maker\Maker;

use Doctrine\DBAL\Types\Type;
use JetBrains\PhpStorm\NoReturn;
use Mirko\T3maker\Doctrine\EntityRelation;
use Mirko\T3maker\FileManager;
use Mirko\T3maker\Generator\EntityClassGenerator;
use Mirko\T3maker\Generator\Generator;
use Mirko\T3maker\Utility\ClassSourceManipulator;
use Mirko\T3maker\Utility\PackageDetails;
use Mirko\T3maker\Utility\StringUtility;
use Mirko\T3maker\Utility\Typo3Utility;
use Mirko\T3maker\Validator\ClassValidator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ModelMaker extends AbstractMaker
{
    public function __construct(private EntityClassGenerator $entityClassGenerator, private FileManager $fileManager)
    {
    }

    #[NoReturn] public function generate(InputInterface $input, SymfonyStyle $io, Generator $generator): void
    {
        $overwrite = $input->getOption('overwrite');
        $extensionName = $input->getArgument('extensionName');
        $package = PackageDetails::createInstance($extensionName);
        $this->fetchExtensionNamespace($io, $package);
        $this->fileManager->setRootDirectory(Typo3Utility::getExtensionPath($package->getName()));

        $entityClassDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            $package->getNamespace() . 'Domain\\Model\\',
        );

        $classExists = class_exists($entityClassDetails->getFullName());

        if (!$classExists) {
            $entityPath = $this->entityClassGenerator->generateEntityClass(
                $entityClassDetails,
                $package,
            );

            $generator->writeChanges();
        }

        if ($classExists) {
            $entityPath = $this->getPathOfClass($entityClassDetails->getFullName());
            $io->text(
                [
                    'Your entity already exists! So let\'s add some new fields!',
                ]
            );
        } else {
            $io->text(
                [
                    '',
                    'Entity generated! Now let\'s add some fields!',
                    'You can always add more fields later manually or by re-running this command.',
                ]
            );
        }

        $currentFields = $this->getPropertyNames($entityClassDetails->getFullName());
        $manipulator = $this->createClassManipulator($entityPath, $io, $overwrite);

        $isFirstField = true;
        while (true) {
            $newField = $this->askForNextField($io, $currentFields, $entityClassDetails->getFullName(), $isFirstField);
            $isFirstField = false;

            if (null === $newField) {
                break;
            }

            $fileManagerOperations = [];
            $fileManagerOperations[$entityPath] = $manipulator;

            if (\is_array($newField)) {
                $annotationOptions = $newField;
                unset($annotationOptions['fieldName']);
                $manipulator->addEntityField($newField['fieldName'], $annotationOptions);

                $currentFields[] = $newField['fieldName'];
            } elseif ($newField instanceof EntityRelation) {
                //TODO implement relation type
            } else {
                throw new \Exception('Invalid value');
            }

            foreach ($fileManagerOperations as $path => $manipulatorOrMessage) {
                if (is_string($manipulator)) {
                    $io->comment($manipulatorOrMessage);
                } else {
                    $this->fileManager->dumpFile($path, $manipulatorOrMessage->getSourceCode());
                }
            }
        }
        $this->writeSuccessMessage($io);
        $io->text(
            [
                'Next: When you\'re ready, create a migration with <info>php bin/console make:migration</info>',
                '',
                'Next: When you\'re ready, create a TCA with <info>php bin/console make:tca</info>',
            ]
        );

    }

    private function getPropertyNames(string $class): array
    {
        if (!class_exists($class)) {
            return [];
        }

        $reflClass = new \ReflectionClass($class);

        return array_map(static fn(\ReflectionProperty $prop) => $prop->getName(), $reflClass->getProperties());
    }

    private function createClassManipulator(string $path, SymfonyStyle $io, bool $overwrite): ClassSourceManipulator
    {
        $manipulator = new ClassSourceManipulator(
            sourceCode: $this->fileManager->getFileContents($path),
            overwrite: $overwrite,
        );

        $manipulator->setIo($io);

        return $manipulator;
    }

    private function askForNextField(
        SymfonyStyle $io,
        array $fields,
        string $entityClass,
        bool $isFirstField
    ): array|null {
        $io->writeln('');

        if ($isFirstField) {
            $questionText = 'New property name (press <return> to stop adding fields)';
        } else {
            $questionText = 'Add another property? Enter the property name (or press <return> to stop adding fields)';
        }

        $fieldName = $io->ask(
            $questionText,
            null,
            function ($name) use ($fields) {
                // allow it to be empty
                if (!$name) {
                    return $name;
                }

                if (\in_array($name, $fields)) {
                    throw new \InvalidArgumentException(sprintf('The "%s" property already exists.', $name));
                }

                return ClassValidator::validateDoctrineFieldName($name);
            }
        );

        if (!$fieldName) {
            return null;
        }

        $defaultType = 'string';
        // try to guess the type by the field name prefix/suffix
        // convert to snake case for simplicity
        $snakeCasedField = StringUtility::asSnakeCase($fieldName);

        if ('_at' === $suffix = substr($snakeCasedField, -3)) {
            $defaultType = 'datetime_immutable';
        } elseif ('_id' === $suffix) {
            $defaultType = 'integer';
        } elseif (str_starts_with($snakeCasedField, 'is_')) {
            $defaultType = 'boolean';
        } elseif (str_starts_with($snakeCasedField, 'has_')) {
            $defaultType = 'boolean';
        } elseif ('uuid' === $snakeCasedField) {
            $defaultType = Type::hasType('uuid') ? 'uuid' : 'guid';
        } elseif ('guid' === $snakeCasedField) {
            $defaultType = 'guid';
        }

        $type = null;
        $types = $this->getTypesMap();

        $allValidTypes = array_merge(
            array_keys($types),
            EntityRelation::getValidRelationTypes(),
            ['relation']
        );
        while (null === $type) {
            $question = new Question('Field type (enter <comment>?</comment> to see all types)', $defaultType);
            $question->setAutocompleterValues($allValidTypes);
            $type = $io->askQuestion($question);

            if ('?' === $type) {
                $this->printAvailableTypes($io);
                $io->writeln('');

                $type = null;
            } elseif (!in_array($type, $allValidTypes, true)) {
                $this->printAvailableTypes($io);
                $io->error(sprintf('Invalid type "%s".', $type));
                $io->writeln('');

                $type = null;
            }
        }

        if ('relation' === $type || in_array($type, EntityRelation::getValidRelationTypes(), true)) {
            //TODO implement relation types
            $io->warning("Sorry but creating relation is not possible at this moment, will coming soon");
//            return $this->askRelationDetails($io, $entityClass, $type, $fieldName);
        }

        // this is a normal field
        $data = ['fieldName' => $fieldName, 'type' => $type];
        if ('string' === $type) {
            // default to 255, avoid the question
            $data['length'] = $io->ask('Field length', "255", [ClassValidator::class, 'validateLength']);
        } elseif ('decimal' === $type) {
            // 10 is the default value given in \Doctrine\DBAL\Schema\Column::$_precision
            $data['precision'] = $io->ask(
                'Precision (total number of digits stored: 100.00 would be 5)',
                "10",
                [ClassValidator::class, 'validatePrecision']
            );

            // 0 is the default value given in \Doctrine\DBAL\Schema\Column::$_scale
            $data['scale'] = $io->ask(
                'Scale (number of decimals to store: 100.00 would be 2)',
                "0",
                [ClassValidator::class, 'validateScale']
            );
        }

        if ($io->confirm('Can this field be null in the database (nullable)', false)) {
            $data['nullable'] = true;
        }

        return $data;
    }

    private function getTypesMap(): array
    {
        $types = Type::getTypesMap();

        // remove deprecated json_array if it exists
        if (\defined(sprintf('%s::JSON_ARRAY', Type::class))) {
            unset($types[Type::JSON_ARRAY]);
        }

        return $types;
    }

    private function printAvailableTypes(SymfonyStyle $io): void
    {
        $allTypes = $this->getTypesMap();

        $typesTable = [
            'main' => [
                'string' => [],
                'text' => [],
                'boolean' => [],
                'integer' => ['smallint', 'bigint'],
                'float' => [],
            ],
            'relation' => [
                'relation' => 'a wizard will help you build the relation',
                EntityRelation::MANY_TO_ONE => [],
                EntityRelation::ONE_TO_MANY => [],
                EntityRelation::MANY_TO_MANY => [],
                EntityRelation::ONE_TO_ONE => [],
            ],
            'array_object' => [
                'array' => [],
                'object' => [ObjectStorage::class],
            ],
            'date_time' => [
                'datetime' => ['datetime_immutable'],
                'date' => ['date_immutable'],
                'time' => ['time_immutable'],
            ],
        ];

        $printSection = static function (array $sectionTypes) use ($io, &$allTypes) {
            foreach ($sectionTypes as $mainType => $subTypes) {
                unset($allTypes[$mainType]);
                $line = sprintf('  * <comment>%s</comment>', $mainType);

                if (\is_string($subTypes) && $subTypes) {
                    $line .= sprintf(' (%s)', $subTypes);
                } elseif (\is_array($subTypes) && !empty($subTypes)) {
                    $line .= sprintf(
                        ' (or %s)',
                        implode(
                            ', ',
                            array_map(
                                static fn($subType) => sprintf('<comment>%s</comment>', $subType),
                                $subTypes
                            )
                        )
                    );

                    foreach ($subTypes as $subType) {
                        unset($allTypes[$subType]);
                    }
                }

                $io->writeln($line);
            }

            $io->writeln('');
        };

        $io->writeln('<info>Main Types</info>');
        $printSection($typesTable['main']);

        $io->writeln('<info>Relationships/Associations</info>');
        $printSection($typesTable['relation']);

        $io->writeln('<info>Array/Object Types</info>');
        $printSection($typesTable['array_object']);

        $io->writeln('<info>Date/Time Types</info>');
        $printSection($typesTable['date_time']);

        $io->writeln('<info>Other Types</info>');
        // empty the values
        $allTypes = array_map(static fn() => [], $allTypes);
        $printSection($allTypes);
    }
}