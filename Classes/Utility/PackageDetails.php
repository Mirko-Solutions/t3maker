<?php

declare(strict_types=1);

namespace Mirko\T3maker\Utility;

class PackageDetails
{
    public function __construct(
        private string $name,
        private array $composerNamespaces,
        private string $composerName,
        private string $namespace = ''
    ) {
    }

    public static function createInstance(string $extensionName): PackageDetails
    {
        $package = PackageUtility::getPackage($extensionName);
        $composerName = $package->getValueFromComposerManifest('name');
        $composerNamespaces = AutoloadUtility::getPackageNamespace($package);

        return new PackageDetails($extensionName, $composerNamespaces, $composerName);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getComposerName(): string
    {
        return $this->composerName;
    }

    /**
     * @return array
     */
    public function getComposerNamespaces(): array
    {
        return $this->composerNamespaces;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }
}
