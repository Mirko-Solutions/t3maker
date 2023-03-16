<?php

declare(strict_types=1);

namespace Mirko\T3maker\Maker;

use Mirko\T3maker\FileManager;
use Mirko\T3maker\Generator\Generator;
use Mirko\T3maker\Generator\TCAGenerator;
use Mirko\T3maker\Parser\ModelParser;
use Mirko\T3maker\Typo3\TCA\TCAColumnFactory;
use Mirko\T3maker\Utility\ClassSourceManipulator;
use Mirko\T3maker\Utility\PackageDetails;
use Mirko\T3maker\Utility\StringUtility;
use Mirko\T3maker\Utility\TCASourceManipulator;
use Mirko\T3maker\Utility\Typo3Utility;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class TCAMaker extends AbstractMaker
{

    public function __construct(
        private TCAGenerator $TCAGenerator,
        private FileManager $fileManager,
        private TCAColumnFactory $columnFactory
    ) {

    }

    public function generate(InputInterface $input, SymfonyStyle $io, Generator $generator): void
    {
        $overwrite = $input->getOption('overwrite');
        $extensionName = $input->getArgument('extensionName');
        $name = $input->getArgument('name');
        $package = PackageDetails::createInstance($extensionName);
        $this->fileManager->setRootDirectory(Typo3Utility::getExtensionPath($package->getName()));
        $this->fileManager->setIO($io);
        $modelReflection = new \ReflectionClass($name);
        $extensionRelativePath = $this->TCAGenerator->getTcaExtensionFilePath($package, $modelReflection);
        $tcaPath = $this->fileManager->absolutizePath($extensionRelativePath);

        if ($this->fileManager->fileExists($tcaPath)) {
            $io->text(
                [
                    '',
                    'TCA Exist! Now let\'s configure fields!',
                ]
            );
        } else {
            $tcaPath = $this->TCAGenerator->generateTCAFromModel($package, $modelReflection);
            $generator->writeChanges();
            $io->text(
                [
                    '',
                    'TCA Created! Now let\'s configure fields!',
                ]
            );
        }

        $columnConfigurations = ModelParser::getTCAProperties($modelReflection);
        $manipulator = $this->createTCAManipulator($tcaPath, $io, $overwrite);

        while (true) {
            $columnName = $this->askForNextField($io, $columnConfigurations);

            if ($columnName === null) {
                break;
            }

            $newConfig = $this->columnFactory->createColumnConfigForTableColumn(
                $modelReflection->getProperty(StringUtility::pluralCamelCaseToSingular($columnName)),
                $io
            );

            $manipulator->updateColumnConfig($columnName, $newConfig);

            if (is_string($manipulator)) {
                $io->comment($manipulator);
            } else {
                $this->fileManager->dumpFile($tcaPath, $manipulator->getSourceCode());
            }
        }

        $this->writeSuccessMessage($io);
    }

    private function createTCAManipulator(string $path, SymfonyStyle $io, bool $overwrite)
    {
        $manipulator = new TCASourceManipulator(
            filePath: $path,
            overwrite: $overwrite,
        );

        $manipulator->setIo($io);

        return $manipulator;
    }

    private function askForNextField(
        SymfonyStyle $io,
        array $columnConfigurations,
    ): string|null {
        $io->writeln('');

        $choices = array_keys($columnConfigurations);
        $question = new ChoiceQuestion(
            "for which column you want to edit configuration?",
            $choices,
            null
        );

        $question->setValidator(function ($value) use ($choices) {
            if ($value === null) {
                return null;
            }

            if (array_key_exists($value, $choices) || in_array($value, $choices, true)) {
                return $value;
            }

            throw new InvalidArgumentException(sprintf('Value "%s" is invalid', $value));
        });

        $question->setNormalizer(
            function ($value) use ($choices) {
                if ($value === null) {
                    return null;
                }
                return array_key_exists($value, $choices) ? $choices[$value] : $value;
            }
        );

        return $io->askQuestion($question);
    }
}