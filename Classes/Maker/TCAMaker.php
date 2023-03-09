<?php

declare(strict_types=1);

namespace Mirko\T3maker\Maker;

use Mirko\T3maker\FileManager;
use Mirko\T3maker\Generator\Generator;
use Mirko\T3maker\Generator\TCAGenerator;
use Mirko\T3maker\Utility\PackageDetails;
use Mirko\T3maker\Utility\Typo3Utility;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TCAMaker extends AbstractMaker
{

    public function __construct(private TCAGenerator $TCAGenerator, private FileManager $fileManager)
    {

    }

    public function generate(InputInterface $input, SymfonyStyle $io, Generator $generator): void
    {
        $overwrite = $input->getOption('overwrite');
        $extensionName = $input->getArgument('extensionName');
        $name = $input->getArgument('name');
        $package = PackageDetails::createInstance($extensionName);
        $this->fileManager->setRootDirectory(Typo3Utility::getExtensionPath($package->getName()));
        $this->TCAGenerator->generateTCAFromModel($package, $name);

        $generator->writeChanges();
    }
}