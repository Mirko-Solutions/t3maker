<?php

declare(strict_types=1);

namespace Mirko\T3maker\Maker;

use Doctrine\DBAL\Types\Type;
use Exception;
use InvalidArgumentException;
use JetBrains\PhpStorm\NoReturn;
use Mirko\T3maker\Doctrine\EntityRelation;
use Mirko\T3maker\FileManager;
use Mirko\T3maker\Generator\EntityClassGenerator;
use Mirko\T3maker\Generator\Generator;
use Mirko\T3maker\Utility\ClassSourceManipulator;
use Mirko\T3maker\Utility\PackageDetails;
use Mirko\T3maker\Utility\PackageUtility;
use Mirko\T3maker\Utility\StringUtility;
use Mirko\T3maker\Utility\Typo3Utility;
use Mirko\T3maker\Validator\ClassValidator;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ModelMaker extends AbstractMaker
{
    public const MODEL_NAME_SPACE = 'Domain\\Model';

    private PackageDetails $package;

    public function __construct(
        private EntityClassGenerator $entityClassGenerator,
        private FileManager $fileManager
    ) {
    }

    #[NoReturn] public function generate(InputInterface $input, SymfonyStyle $io, Generator $generator): void
    {
        $overwrite = $input->getOption('overwrite');
        $extensionName = $input->getArgument('extensionName');
        $package = PackageDetails::createInstance($extensionName);
        $this->package = $package;
        $this->fetchExtensionNamespace($io, $package);
        $this->fileManager->setRootDirectory(Typo3Utility::getExtensionPath($package->getName()));

        $entityClassDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            $package->getNamespace() . self::MODEL_NAME_SPACE,
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

            if ($newField === null) {
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
                // both overridden below for OneToMany
                $newFieldName = $newField->getOwningProperty();
                if ($newField->isSelfReferencing()) {
                    $otherManipulatorFilename = $entityPath;
                    $otherManipulator = $manipulator;
                } else {
                    $otherManipulatorFilename = $this->getPathOfClass($newField->getInverseClass());
                    $otherManipulator = $this->createClassManipulator($otherManipulatorFilename, $io, $overwrite);
                }
                switch ($newField->getType()) {
                    case EntityRelation::MANY_TO_ONE:
                        if ($newField->getOwningClass() === $entityClassDetails->getFullName()) {
                            // THIS class will receive the ManyToOne
                            $manipulator->addManyToOneRelation($newField->getOwningRelation());

                            if ($newField->getMapInverseRelation()) {
                                $otherManipulator->addOneToManyRelation($newField->getInverseRelation());
                            }
                        } else {
                            // the new field being added to THIS entity is the inverse
                            $newFieldName = $newField->getInverseProperty();
                            $otherManipulatorFilename = $this->getPathOfClass($newField->getOwningClass());
                            $otherManipulator = $this->createClassManipulator(
                                $otherManipulatorFilename,
                                $io,
                                $overwrite
                            );

                            // The *other* class will receive the ManyToOne
                            $otherManipulator->addManyToOneRelation($newField->getOwningRelation());
                            if (!$newField->getMapInverseRelation()) {
                                throw new Exception(
                                    'Somehow a OneToMany relationship is being created,
                                    but the inverse side will not be mapped?'
                                );
                            }
                            $manipulator->addOneToManyRelation($newField->getInverseRelation());
                        }

                        break;
                    case EntityRelation::MANY_TO_MANY:
                        $manipulator->addManyToManyRelation($newField->getOwningRelation());
                        if ($newField->getMapInverseRelation()) {
                            $otherManipulator->addManyToManyRelation($newField->getInverseRelation());
                        }

                        break;
                    case EntityRelation::ONE_TO_ONE:
                        $manipulator->addOneToOneRelation($newField->getOwningRelation());
                        if ($newField->getMapInverseRelation()) {
                            $otherManipulator->addOneToOneRelation($newField->getInverseRelation());
                        }

                        break;
                    default:
                        throw new Exception('Invalid relation type');
                }

                // save the inverse side if it's being mapped
                if ($newField->getMapInverseRelation()) {
                    $fileManagerOperations[$otherManipulatorFilename] = $otherManipulator;
                }
                $currentFields[] = $newFieldName;
            } else {
                throw new Exception('Invalid value');
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
                'Next: When you\'re ready, create a TCA with <info>php bin/typo3 make:tca</info>',
            ]
        );
    }

    private function getPropertyNames(string $class): array
    {
        if (!class_exists($class)) {
            return [];
        }

        $reflClass = new ReflectionClass($class);

        return array_map(static fn (ReflectionProperty $prop) => $prop->getName(), $reflClass->getProperties());
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
    ): EntityRelation|array|null {
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
                    throw new InvalidArgumentException(sprintf('The "%s" property already exists.', $name));
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
        $suffix = substr($snakeCasedField, -3);
        if ($suffix === '_at') {
            $defaultType = 'datetime_immutable';
        } elseif ($suffix === '_id') {
            $defaultType = 'integer';
        } elseif (str_starts_with($snakeCasedField, 'is_')) {
            $defaultType = 'boolean';
        } elseif (str_starts_with($snakeCasedField, 'has_')) {
            $defaultType = 'boolean';
        } elseif ($snakeCasedField === 'uuid') {
            $defaultType = Type::hasType('uuid') ? 'uuid' : 'guid';
        } elseif ($snakeCasedField === 'guid') {
            $defaultType = 'guid';
        }

        $type = null;
        $types = $this->getTypesMap();

        $allValidTypes = array_merge(
            array_keys($types),
            EntityRelation::getValidRelationTypes(),
            ['relation']
        );
        while ($type === null) {
            $question = new Question('Field type (enter <comment>?</comment> to see all types)', $defaultType);
            $question->setAutocompleterValues($allValidTypes);
            $type = $io->askQuestion($question);

            if ($type === '?') {
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

        if ($type === 'relation' || in_array($type, EntityRelation::getValidRelationTypes(), true)) {
            //TODO implement relation types
            return $this->askRelationDetails($io, $entityClass, $type, $fieldName);
        }

        // this is a normal field
        $data = ['fieldName' => $fieldName, 'type' => $type];
        if ($type === 'string') {
            // default to 255, avoid the question
            $data['length'] = $io->ask('Field length', '255', [ClassValidator::class, 'validateLength']);
        } elseif ($type === 'decimal') {
            // 10 is the default value given in \Doctrine\DBAL\Schema\Column::$_precision
            $data['precision'] = $io->ask(
                'Precision (total number of digits stored: 100.00 would be 5)',
                '10',
                [ClassValidator::class, 'validatePrecision']
            );

            // 0 is the default value given in \Doctrine\DBAL\Schema\Column::$_scale
            $data['scale'] = $io->ask(
                'Scale (number of decimals to store: 100.00 would be 2)',
                '0',
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

        $printSection = static function (array $sectionTypes) use ($io, &$allTypes): void {
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
                                static fn ($subType) => sprintf('<comment>%s</comment>', $subType),
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
        $allTypes = array_map(static fn () => [], $allTypes);
        $printSection($allTypes);
    }

    private function askRelationDetails(
        SymfonyStyle $io,
        string $generatedEntityClass,
        string $type,
        string $newFieldName
    ): EntityRelation {
        // ask the targetEntity
        $targetEntityClass = null;
        while ($targetEntityClass === null) {
            $question = $this->createEntityClassQuestion('What class should this entity be related to?');

            $answeredEntityClass = $io->askQuestion($question);
            // find the correct class name - but give priority over looking
            // in the Entity namespace versus just checking the full class
            // name to avoid issues with classes like "Directory" that exist
            // in PHP's core.
            if (class_exists($this->package->getNamespace() . '\\' . $answeredEntityClass)) {
                $targetEntityClass = $this->package->getNamespace() . '\\' . $answeredEntityClass;
            } elseif (class_exists($answeredEntityClass)) {
                $targetEntityClass = $answeredEntityClass;
            } else {
                $io->error(sprintf('Unknown class "%s"', $answeredEntityClass));
                continue;
            }
        }

        // help the user select the type
        if ($type === 'relation') {
            $type = $this->askRelationType($io, $generatedEntityClass, $targetEntityClass);
        }

        $askFieldName = fn (string $targetClass, string $defaultValue) => $io->ask(
            sprintf('New field name inside %s', StringUtility::getShortClassName($targetClass)),
            $defaultValue,
            function ($name) use ($targetClass) {
                // it's still *possible* to create duplicate properties - by
                // trying to generate the same property 2 times during the
                // same make:entity run. property_exists() only knows about
                // properties that *originally* existed on this class.
                if (property_exists($targetClass, $name)) {
                    throw new InvalidArgumentException(
                        sprintf('The "%s" class already has a "%s" property.', $targetClass, $name)
                    );
                }

                return ClassValidator::validateDoctrineFieldName($name);
            }
        );

        $askIsNullable = static fn (string $propertyName, string $targetClass) => $io->confirm(
            sprintf(
                'Is the <comment>%s</comment>.<comment>%s</comment> property allowed to be null (nullable)?',
                StringUtility::getShortClassName($targetClass),
                $propertyName
            )
        );

        $askOrphanRemoval = static function (string $owningClass, string $inverseClass) use ($io) {
            $io->text(
                [
                    'Do you want to activate <comment>orphanRemoval</comment> on your relationship?',
                    sprintf(
                        'A <comment>%s</comment>
                                is "orphaned" when it is removed from its related <comment>%s</comment>.',
                        StringUtility::getShortClassName($owningClass),
                        StringUtility::getShortClassName($inverseClass)
                    ),
                    sprintf(
                        'e.g. <comment>$%s->remove%s($%s)</comment>',
                        StringUtility::asLowerCamelCase(StringUtility::getShortClassName($inverseClass)),
                        StringUtility::asCamelCase(StringUtility::getShortClassName($owningClass)),
                        StringUtility::asLowerCamelCase(StringUtility::getShortClassName($owningClass))
                    ),
                    '',
                    sprintf(
                        'NOTE: If a <comment>%s</comment>
                                may *change* from one <comment>%s</comment> to another, answer "no".',
                        StringUtility::getShortClassName($owningClass),
                        StringUtility::getShortClassName($inverseClass)
                    ),
                ]
            );

            return $io->confirm(
                sprintf(
                    'Do you want to automatically delete orphaned <comment>%s</comment> objects (orphanRemoval)?',
                    $owningClass
                ),
                false
            );
        };

        $askInverseSide = function (EntityRelation $relation) use ($io): void {
            if ($this->isClassInVendor($relation->getInverseClass())) {
                $relation->setMapInverseRelation(false);

                return;
            }

            // recommend an inverse side, except for OneToOne, where it's inefficient
            $recommendMappingInverse = $relation->getType() !== EntityRelation::ONE_TO_ONE;

            $getterMethodName = 'get' . StringUtility::asCamelCase(
                StringUtility::getShortClassName($relation->getOwningClass())
            );
            if ($relation->getType() !== EntityRelation::ONE_TO_ONE) {
                // pluralize!
                $getterMethodName = StringUtility::singularCamelCaseToPluralCamelCase($getterMethodName);
            }
            $mapInverse = $io->confirm(
                sprintf(
                    'Do you want to add a new property to <comment>%s</comment> so that you can access/update
                            <comment>%s</comment> objects from it - e.g. <comment>$%s->%s()</comment>?',
                    StringUtility::getShortClassName($relation->getInverseClass()),
                    StringUtility::getShortClassName($relation->getOwningClass()),
                    StringUtility::asLowerCamelCase(StringUtility::getShortClassName($relation->getInverseClass())),
                    $getterMethodName
                ),
                $recommendMappingInverse
            );
            $relation->setMapInverseRelation($mapInverse);
        };

        switch ($type) {
            case EntityRelation::ONE_TO_MANY:
                // we *actually* create a ManyToOne, but populate it differently
                $relation = new EntityRelation(
                    EntityRelation::MANY_TO_ONE,
                    $targetEntityClass,
                    $generatedEntityClass
                );
                $relation->setInverseProperty($newFieldName);

                $io->comment(
                    sprintf(
                        'A new property will also be added to the <comment>%s</comment> class so that
                                you can access and set the related <comment>%s</comment> object from it.',
                        StringUtility::getShortClassName($relation->getOwningClass()),
                        StringUtility::getShortClassName($relation->getInverseClass())
                    )
                );
                $relation->setOwningProperty(
                    $askFieldName(
                        $relation->getOwningClass(),
                        StringUtility::asLowerCamelCase(StringUtility::getShortClassName($relation->getInverseClass()))
                    )
                );

                $relation->setIsNullable(
                    $askIsNullable(
                        $relation->getOwningProperty(),
                        $relation->getOwningClass()
                    )
                );

                if (!$relation->isNullable()) {
                    $relation->setOrphanRemoval(
                        $askOrphanRemoval(
                            $relation->getOwningClass(),
                            $relation->getInverseClass()
                        )
                    );
                }

                break;
            case EntityRelation::MANY_TO_MANY:
                $relation = new EntityRelation(
                    EntityRelation::MANY_TO_MANY,
                    $generatedEntityClass,
                    $targetEntityClass
                );
                $relation->setOwningProperty($newFieldName);

                $askInverseSide($relation);
                if ($relation->getMapInverseRelation()) {
                    $io->comment(
                        sprintf(
                            'A new property will also be added to the <comment>%s</comment> class so that
                                    you can access the related <comment>%s</comment> objects from it.',
                            StringUtility::getShortClassName($relation->getInverseClass()),
                            StringUtility::getShortClassName($relation->getOwningClass())
                        )
                    );
                    $relation->setInverseProperty(
                        $askFieldName(
                            $relation->getInverseClass(),
                            StringUtility::singularCamelCaseToPluralCamelCase(
                                StringUtility::getShortClassName($relation->getOwningClass())
                            )
                        )
                    );
                }

                break;
            case EntityRelation::ONE_TO_ONE:
                $relation = new EntityRelation(
                    EntityRelation::ONE_TO_ONE,
                    $generatedEntityClass,
                    $targetEntityClass
                );
                $relation->setOwningProperty($newFieldName);

                $relation->setIsNullable(
                    $askIsNullable(
                        $relation->getOwningProperty(),
                        $relation->getOwningClass()
                    )
                );

                $askInverseSide($relation);
                if ($relation->getMapInverseRelation()) {
                    $io->comment(
                        sprintf(
                            'A new property will also be added to the <comment>%s</comment> class so that
                                    you can access the related <comment>%s</comment> object from it.',
                            StringUtility::getShortClassName($relation->getInverseClass()),
                            StringUtility::getShortClassName($relation->getOwningClass())
                        )
                    );
                    $relation->setInverseProperty(
                        $askFieldName(
                            $relation->getInverseClass(),
                            StringUtility::asLowerCamelCase(
                                StringUtility::getShortClassName($relation->getOwningClass())
                            )
                        )
                    );
                }

                break;
            default:
                throw new InvalidArgumentException('Invalid type: ' . $type);
        }

        return $relation;
    }

    private function createEntityClassQuestion(string $questionText): Question
    {
        $question = new Question($questionText);
        $question->setValidator([ClassValidator::class, 'notBlank']);
        $package = PackageUtility::getPackage($this->package->getName());
        $choices = PackageUtility::getPackageClassesByNamespace($package, self::MODEL_NAME_SPACE);
        $question->setAutocompleterValues($choices);

        return $question;
    }

    private function askRelationType(SymfonyStyle $io, string $entityClass, string $targetEntityClass)
    {
        $io->writeln('What type of relationship is this?');

        $originalEntityShort = StringUtility::getShortClassName($entityClass);
        $targetEntityShort = StringUtility::getShortClassName($targetEntityClass);
        $rows = [];
        $rows[] = [
            EntityRelation::MANY_TO_ONE,
            sprintf(
                "Each <comment>%s</comment> relates to (has) <info>one</info> <comment>%s</comment>.\n Each
                       <comment>%s</comment> can relate to (can have) <info>many</info> <comment>%s</comment> objects.",
                $originalEntityShort,
                $targetEntityShort,
                $targetEntityShort,
                $originalEntityShort
            ),
        ];
        $rows[] = ['', ''];
        $rows[] = [
            EntityRelation::ONE_TO_MANY,
            sprintf(
                "Each <comment>%s</comment> can relate to (can have) <info>many</info> <comment>%s</comment>
                        objects.\nEach <comment>%s</comment> relates to (has) <info>one</info> <comment>%s</comment>.",
                $originalEntityShort,
                $targetEntityShort,
                $targetEntityShort,
                $originalEntityShort
            ),
        ];
        $rows[] = ['', ''];
        $rows[] = [
            EntityRelation::MANY_TO_MANY,
            sprintf(
                "Each <comment>%s</comment> can relate to (can have) <info>many</info> <comment>%s</comment>
                        objects.\nEach <comment>%s</comment> can also relate to (can also have) <info>many</info>
                        <comment>%s</comment> objects.",
                $originalEntityShort,
                $targetEntityShort,
                $targetEntityShort,
                $originalEntityShort
            ),
        ];
        $rows[] = ['', ''];
        $rows[] = [
            EntityRelation::ONE_TO_ONE,
            sprintf(
                "Each <comment>%s</comment> relates to (has) exactly <info>one</info> <comment>%s</comment>.\n
                        Each <comment>%s</comment> also relates to (has) exactly <info>one</info>
                        <comment>%s</comment>.",
                $originalEntityShort,
                $targetEntityShort,
                $targetEntityShort,
                $originalEntityShort
            ),
        ];

        $io->table(
            [
                'Type',
                'Description',
            ],
            $rows
        );

        $question = new Question(
            sprintf(
                'Relation type? [%s]',
                implode(', ', EntityRelation::getValidRelationTypes())
            )
        );
        $question->setAutocompleterValues(EntityRelation::getValidRelationTypes());
        $question->setValidator(
            function ($type) {
                if (!\in_array($type, EntityRelation::getValidRelationTypes())) {
                    throw new InvalidArgumentException(
                        sprintf('Invalid type: use one of: %s', implode(', ', EntityRelation::getValidRelationTypes()))
                    );
                }

                return $type;
            }
        );

        return $io->askQuestion($question);
    }

    private function isClassInVendor(string $class): bool
    {
        $path = $this->getPathOfClass($class);

        return $this->fileManager->isPathInVendor($path);
    }
}
