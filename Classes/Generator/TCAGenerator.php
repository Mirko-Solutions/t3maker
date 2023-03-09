<?php

declare(strict_types=1);


namespace Mirko\T3maker\Generator;

use Mirko\T3maker\Parser\ModelParser;
use Mirko\T3maker\Utility\PackageDetails;
use Mirko\T3maker\Utility\StringUtility;
use ReflectionClass;
use ReflectionException;

class TCAGenerator
{
    public function __construct(private Generator $generator)
    {
    }

    /**
     * @param PackageDetails $package
     * @param string $class
     * @return string
     * @throws ReflectionException
     */
    public function generateTCAFromModel(PackageDetails $package, string $class)
    {
        $modelReflection = new ReflectionClass($class);

        $TCAColumns = ModelParser::getTCAProperties($modelReflection);
        $columnKeys = array_keys($TCAColumns);
        $columnKeysString = implode(',', $columnKeys);

        return $this->generator->generateFile(
            $package,
            'Configuration/TCA/Test/' . $this->generateTCAFileName(
                $package->getName(),
                $modelReflection->getShortName()
            ),
            'doctrine/TCA.tpl.php',
            [
                'title' => $modelReflection->getShortName(),
                'label' => $columnKeys[0],
                'interfaceShowRecordFieldList' => $columnKeysString,
                'typesShowItem' => $columnKeysString,
                'palettesShowItem' => $columnKeysString,
                'columns' => var_export($TCAColumns, true),
            ]
        );
    }

    private function generateTCAFileName($extKey, $modelName): string
    {
        $modelName = StringUtility::addSuffix(strtolower($modelName), '.php');
        return "tx_{$extKey}_domain_model_{$modelName}";
    }
}