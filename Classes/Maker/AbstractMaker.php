<?php

declare(strict_types=1);

namespace Mirko\T3maker\Maker;

use Mirko\T3maker\Utility\PackageDetails;
use Mirko\T3maker\Validator\ClassValidator;
use ReflectionClass;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractMaker implements MakerInterface
{
    /**
     * Writes a success message.
     */
    protected function writeSuccessMessage(SymfonyStyle $io): void
    {
        $io->writeln(' <bg=green;fg=white> Success! </>');
        $io->newLine();
    }

    protected function fetchExtensionNamespace(SymfonyStyle $io, $package): void
    {
        if (count($package->getComposerNamespaces()) === 1) {
            $namespace = array_key_first($package->getComposerNamespaces());

            $io->writeln('`' . $namespace . '` namespace will be used');
            // TODO: Logic when answer is false.
            $package->setNamespace($namespace);
            return;
        }

        $this->multipleNamespaceFoundQuestion($io, $package);
    }

    protected function multipleNamespaceFoundQuestion(SymfonyStyle $io, PackageDetails $package): void
    {
        $composerNamespaces = array_keys($package->getComposerNamespaces());
        $question = new ChoiceQuestion(
            'Multiple namespaces found. Please specify',
            // choices can also be PHP objects that implement __toString() method
            $composerNamespaces,
            0
        );
        $question->setValidator([ClassValidator::class, 'notEmpty']);
        $question->setNormalizer(
            fn ($value) => array_key_exists($value, $composerNamespaces) ? $composerNamespaces[$value] : $value
        );
        $answer = $io->askQuestion($question);

        $package->setNamespace($answer);
    }

    protected function askConfirmationQuestion(SymfonyStyle $io, string $text)
    {
        $question = new ConfirmationQuestion($text, true);
        return $io->askQuestion($question);
    }

    protected function createQuestion(string $questionText): Question
    {
        $question = new Question($questionText);
        $question->setValidator([ClassValidator::class, 'notEmpty']);

        return $question;
    }

    protected function getPathOfClass(string $class): string
    {
        return (new ReflectionClass($class))->getFileName();
    }
}
