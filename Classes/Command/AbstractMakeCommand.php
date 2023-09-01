<?php

declare(strict_types=1);

namespace Mirko\T3maker\Command;

use JetBrains\PhpStorm\NoReturn;
use LogicException;
use Mirko\T3maker\Generator\Generator;
use Mirko\T3maker\Maker\MakerInterface;
use Mirko\T3maker\Utility\Typo3Utility;
use Mirko\T3maker\Validator\ClassValidator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractMakeCommand extends Command
{
    protected SymfonyStyle $io;

    protected string $extensionName = '';

    protected string $extensionPath = '';

    public function __construct(protected MakerInterface $maker, protected Generator $generator, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'extensionName',
                InputArgument::REQUIRED,
                'name for which extension command will be executed'
            );
    }

    #[NoReturn] protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $extensionName = $input->getArgument('extensionName');
        if ($extensionName) {
            return;
        }

        $argument = $this->getDefinition()->getArgument('extensionName');
        $question = $this->createClassQuestion($argument->getDescription());
        $extensionName = $this->io->askQuestion($question);

        $input->setArgument('extensionName', $extensionName);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!Typo3Utility::isExtensionLoaded($input->getArgument('extensionName'))) {
            $extensionName = $input->getArgument('extensionName');
            $this->io->error('Extension key ' . $extensionName . ' is not loaded!');
            return Command::FAILURE;
        }

        $this->maker->generate($input, $this->io, $this->generator);

        if ($this->generator->hasPendingOperations()) {
            throw new LogicException('Make sure to call the writeChanges() method on the generator.');
        }

        return Command::SUCCESS;
    }

    protected function createClassQuestion(string $questionText): Question
    {
        $question = new Question($questionText);
        $question->setValidator([ClassValidator::class, 'notEmpty']);

        return $question;
    }
}
