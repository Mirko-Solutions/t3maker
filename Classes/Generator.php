<?php

declare(strict_types=1);

namespace Mirko\T3maker;

use Mirko\T3maker\Utility\ClassDetails;
use Mirko\T3maker\Utility\StringUtility;
use Mirko\T3maker\Validator\ClassValidator;

final class Generator
{
    private array $pendingOperations = [];
    private string $namespacePrefix = '';

    public function createClassNameDetails(
        string $name,
        string $namespacePrefix,
        string $suffix = '',
        string $validationErrorMessage = ''
    ): ClassDetails {
        $fullNamespacePrefix = $this->namespacePrefix . '\\' . $namespacePrefix;
        if ('\\' === $name[0]) {
            // class is already "absolute" - leave it alone (but strip opening \)
            $className = substr($name, 1);
        } else {
            $className = rtrim($fullNamespacePrefix, '\\') . '\\' . StringUtility::asClassName($name, $suffix);
        }

        ClassValidator::validateClassName($className, $validationErrorMessage);

        // if this is a custom class, we may be completely different than the namespace prefix
        // the best way can do, is find the PSR4 prefix and use thatgetRelativeName
        if (!str_starts_with($className, $fullNamespacePrefix)) {
            $fullNamespacePrefix = $this->fileManager->getNamespacePrefixForClass($className);
        }

        return new ClassDetails($className, $fullNamespacePrefix, $suffix);
    }

    public function getRootNamespace(): string
    {
        return $this->namespacePrefix;
    }

    public function setRootNamespace(string $namespace): void
    {
        $this->namespacePrefix = $namespace;
    }

    public function hasPendingOperations(): bool
    {
        return !empty($this->pendingOperations);
    }
}