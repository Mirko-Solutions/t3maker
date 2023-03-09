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
            $propertyType = $class->getProperty($propertyName)->getType()?->getName();

            // Add the property to the TCA configuration
            $columns[StringUtility::asSnakeCase($propertyName)] = array(
                'label' => ucfirst($propertyName),
                'config' => array(
                    'type' => self::getTcaType($propertyType),
                    'size' => 30,
                    'eval' => 'trim',
                ),
            );
        }

        return $columns;
    }

    private static function getTcaType($propertyType): string
    {
        return match ($propertyType) {
            'bool' => 'check',
            default => 'input',
        };
    }
}