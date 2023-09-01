<?php

declare(strict_types=1);

namespace Mirko\T3maker\Utility;

use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PackageUtility
{
    public static function getPackage(string $extensionName): Package
    {
        return GeneralUtility::makeInstance(PackageManager::class)->getPackage($extensionName);
    }
    public static function getPackageClassesByNamespace(Package $package, $directNamespace = ''): array
    {
        $finder = new Finder();
        $namespaces = AutoloadUtility::getPackageNamespace($package);
        $classes = [];
        foreach ($namespaces as $namespace => $path) {
            $searchPath = $package->getPackagePath() . $path . StringUtility::asFilePath($directNamespace);
            if (!is_dir($searchPath)) {
                continue;
            }

            $files = $finder->in($searchPath)->files()->name('*.php');

            foreach ($files as $file) {
                $reflectClassName = $namespace . $directNamespace . '\\' . StringUtility::removeSuffix(
                    $file->getFilename(),
                    '.php'
                );
                if (!class_exists($reflectClassName)) {
                    continue;
                }

                $class = new \ReflectionClass($reflectClassName);

                if ($class->isAbstract() || $class->isInterface() || $class->isTrait()) {
                    continue;
                }

                $classes[] = $class->getName();
            }
        }

        return $classes;
    }
}
