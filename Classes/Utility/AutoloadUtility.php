<?php

declare(strict_types=1);

namespace Mirko\T3maker\Utility;

use TYPO3\CMS\Core\Package\Package;

class AutoloadUtility
{
    public static function getPackageNamespace(Package $package): array
    {
        if ($package->getValueFromComposerManifest('autoload')->{'psr-4'}) {
            return get_object_vars($package->getValueFromComposerManifest('autoload')->{'psr-4'});
        }

        if ($package->getValueFromComposerManifest('autoload')->{'psr-0'}) {
            return get_object_vars($package->getValueFromComposerManifest('autoload')->{'psr-0'});
        }

        return [];
    }
}
