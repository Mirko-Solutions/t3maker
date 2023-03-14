<?php

declare(strict_types=1);


namespace Mirko\T3maker\Parser;

use Mirko\T3maker\Utility\StringUtility;

class ModelParser
{
    public static function getTCAProperties(\ReflectionClass $class): array
    {
        $columns = [];
        $properties = $class->getProperties();
        foreach ($properties as $property) {
            if ($property->class !== $class->name) {
                continue;
            }
            // Get the property name and type
            $propertyName = $property->getName();
            // Add the property to the TCA configuration
            $columns[StringUtility::asSnakeCase($propertyName)] = [
                'label' => ucfirst($propertyName),
                'config' => [],
            ];
        }
        return $columns;
    }
}