<?php

declare(strict_types=1);

namespace Mirko\T3maker\Parser;

use Mirko\T3maker\Typo3\TCA\Config\Type\Input;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Symfony\Bundle\MakerBundle\Str;

class ModelParser
{
    public static function getTCAProperties(ReflectionClass $class): array
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
            $columns[Str::asSnakeCase($propertyName)] = [
                'label' => ucfirst($propertyName),
                'config' => [
                    'type' => Input::getTypeName(),
                ],
            ];
        }
        return $columns;
    }

    /**
     * @param ReflectionProperty $property
     *
     * @return array<ReflectionNamedType>
     */
    public static function getPropertyType(ReflectionProperty $property): array
    {
        $propertyType = $property->getType();
        return match (true) {
            $propertyType instanceof ReflectionNamedType => (static fn (): array => [$propertyType])(),
            $propertyType instanceof ReflectionUnionType => (static fn (): array => $propertyType->getTypes())(),
            default => []
        };
    }
}
