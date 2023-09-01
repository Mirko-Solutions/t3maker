<?php

declare(strict_types=1);

namespace Mirko\T3maker\Utility;

class ClassDetails
{
    public function __construct(
        private string $fullClassName,
        private string $namespacePrefix,
        private ?string $suffix = null,
    ) {
        $this->namespacePrefix = trim($namespacePrefix, '\\');
    }

    public function getFullName(): string
    {
        return $this->fullClassName;
    }

    public function getShortName(): string
    {
        return StringUtility::getShortClassName($this->fullClassName);
    }

    /**
     * Returns the original class name the user entered (after
     * being cleaned up).
     *
     * For example, assuming the namespace is App\Entity:
     *      App\Entity\Admin\User => Admin\User
     */
    public function getRelativeName(): string
    {
        return str_replace($this->namespacePrefix . '\\', '', $this->fullClassName);
    }

    public function getRelativeNameWithoutSuffix(): string
    {
        return StringUtility::removeSuffix($this->getRelativeName(), $this->suffix);
    }
}
