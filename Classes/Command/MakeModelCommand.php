<?php

declare(strict_types=1);

namespace Mirko\T3maker\Command;

use JetBrains\PhpStorm\NoReturn;
use Mirko\T3maker\Utility\StringUtility;
use Mirko\T3maker\Utility\Typo3Utility;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Used as the Command class for the makers.
 *
 * @internal
 */
final class MakeModelCommand extends AbstractMakeCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                sprintf(
                    'Class name of the entity to create or update (e.g. <fg=yellow>%s</>)',
                    StringUtility::asClassName(StringUtility::getRandomTerm())
                )
            )
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Overwrite any existing getter/setter methods')
            ->setHelp(file_get_contents(Typo3Utility::getExtensionPath('t3maker') . 'Resources/help/MakeEntity.txt'));
    }

    #[NoReturn] protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        parent::interact($input, $output);

        if ($input->getArgument('name')) {
            return;
        }

        $argument = $this->getDefinition()->getArgument('name');
        $question = $this->createClassQuestion($argument->getDescription());
        $entityClassName = $this->io->askQuestion($question);

        $input->setArgument('name', $entityClassName);
    }
}
