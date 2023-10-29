<?php

declare(strict_types=1);

namespace Mirko\T3maker\Generator;

use Mirko\T3maker\Parser\ModelParser;
use Mirko\T3maker\Utility\PackageDetails;
use ReflectionClass;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\VarExporter\VarExporter;

class TCAGenerator
{
    public function __construct(private Generator $generator)
    {
    }

    /**
     * @param PackageDetails  $package
     * @param ReflectionClass $class
     *
     * @return string
     */
    public function generateTCAFromModel(PackageDetails $package, ReflectionClass $class)
    {
        $TCAColumns = ModelParser::getTCAProperties($class);
        $columnKeys = array_keys($TCAColumns);
        $columnKeysString = implode(',', $columnKeys);

        return $this->generator->generateFile(
            $package,
            $this->getTcaExtensionFilePath($package, $class),
            'doctrine/TCA.tpl.php',
            [
                'title' => $class->getShortName(),
                'label' => $columnKeys[0],
                'interfaceShowRecordFieldList' => $columnKeysString,
                'typesShowItem' => $columnKeysString,
                'palettesShowItem' => $columnKeysString,
                'columns' => VarExporter::export($TCAColumns),
            ]
        );
    }

    public function getTcaExtensionFilePath(PackageDetails $package, ReflectionClass $class): string
    {
        return 'Configuration/TCA/' . $this->generateTCAFileName(
            $package->getName(),
            $class->getShortName()
        );
    }

    private function generateTCAFileName($extKey, $modelName): string
    {
        $modelName = Str::addSuffix(strtolower($modelName), '.php');
        return 'tx_' . $extKey . '_domain_model_' . $modelName;
    }
}
