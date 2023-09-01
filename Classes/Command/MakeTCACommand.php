<?php

declare(strict_types=1);

namespace Mirko\T3maker\Command;

use Mirko\T3maker\Utility\PackageUtility;
use Mirko\T3maker\Utility\Typo3Utility;
use Mirko\T3maker\Validator\ClassValidator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

final class MakeTCACommand extends AbstractMakeCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Class name of the entity to create or update TCA definition.',
            )
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Overwrite whole TCA file')
            ->setHelp(file_get_contents(Typo3Utility::getExtensionPath('t3maker') . 'Resources/help/MakeTCA.txt'));
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        parent::interact($input, $output);

        if ($input->getArgument('name')) {
            return;
        }

        $argument = $this->getDefinition()->getArgument('name');
        $extensionName = $input->getArgument('extensionName');
        $package = PackageUtility::getPackage($extensionName);
        $choices = PackageUtility::getPackageClassesByNamespace($package, 'Domain\\Model');

        $question = new ChoiceQuestion(
            $argument->getDescription(),
            $choices,
            0
        );

        $question->setValidator([ClassValidator::class, 'notEmpty']);
        $question->setNormalizer(
            fn ($value) => array_key_exists($value, $choices) ? $choices[$value] : $value
        );
        $entityClassName = $this->io->askQuestion($question);

        $input->setArgument('name', $entityClassName);
    }
}
