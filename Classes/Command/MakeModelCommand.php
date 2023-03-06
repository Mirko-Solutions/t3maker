<?php

declare(strict_types=1);

namespace Mirko\T3maker\Command;

use JetBrains\PhpStorm\NoReturn;
use Mirko\T3maker\Generator;
use Mirko\T3maker\Maker\MakerInterface;
use Mirko\T3maker\Validator\ClassValidator;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Used as the Command class for the makers.
 *
 * @internal
 */
final class MakeModelCommand extends AbstractMakeCommand
{
    public function __construct(protected MakerInterface $maker, protected Generator $generator, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                sprintf(
                    'Class name of the entity to create or update (e.g. <fg=yellow>%s</>)',
                    Str::asClassName(Str::getRandomTerm())
                )
            )
            ->setHelp(file_get_contents(__DIR__ . '/../Resources/help/MakeEntity.txt'));
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
