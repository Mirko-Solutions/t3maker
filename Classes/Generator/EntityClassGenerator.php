<?php

declare(strict_types=1);

namespace Mirko\T3maker\Generator;

use JetBrains\PhpStorm\NoReturn;
use Mirko\T3maker\Utility\ClassDetails;
use Mirko\T3maker\Utility\PackageDetails;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Repository;

class EntityClassGenerator
{
    public function __construct(private Generator $generator)
    {
    }

    #[NoReturn] public function generateEntityClass(
        ClassDetails $entityClassDetails,
        PackageDetails $package,
        bool $generateRepositoryClass = true,
        bool $broadcast = false
    ): string {
        $repoClassDetails = $this->generator->createClassNameDetails(
            $entityClassDetails->getRelativeName(),
            $package->getNamespace() . 'Domain\\Repository\\',
            'Repository'
        );

        $useStatements = new UseStatementGenerator(
            [
                AbstractEntity::class,
            ]
        );

        $entityPath = $this->generator->generateClass(
            $package,
            $entityClassDetails->getFullName(),
            'doctrine/Entity.tpl.php',
            [
                'use_statements' => $useStatements,
                'broadcast' => $broadcast,
            ]
        );

        if ($generateRepositoryClass) {
            $this->generateRepositoryClass(
                $package,
                $repoClassDetails->getFullName()
            );
        }

        return $entityPath;
    }

    public function generateRepositoryClass(
        $package,
        string $repositoryClass,
    ): void {
        $useStatements = new UseStatementGenerator(
            [
                Repository::class,
            ]
        );

        $this->generator->generateClass(
            $package,
            $repositoryClass,
            'doctrine/Repository.tpl.php',
            [
                'use_statements' => $useStatements,
            ]
        );
    }
}
