<?php

declare(strict_types=1);

namespace Mirko\T3maker\Utility;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class Typo3Utility
{
    public static function getExtensionPath(string $extensionName): string
    {
        return ExtensionManagementUtility::extPath($extensionName);
    }

    public static function isExtensionLoaded(string $extensionName): bool
    {
        return ExtensionManagementUtility::isLoaded($extensionName);
    }
}
