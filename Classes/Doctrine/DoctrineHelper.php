<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mirko\T3maker\Doctrine;

use Doctrine\DBAL\Types\Types;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Ryan Weaver <ryan@knpuniversity.com>
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 *
 * @internal
 */
final class DoctrineHelper
{
    /**
     * Determines if the property-type will make the column type redundant.
     */
    public static function canColumnTypeBeInferredByPropertyType(string $columnType, string $propertyType): bool
    {
        return match ($propertyType) {
            '\\' . \DateInterval::class => $columnType === Types::DATEINTERVAL,
            '\\' . \DateTime::class => $columnType === Types::DATETIME_MUTABLE,
            '\\' . \DateTimeImmutable::class => $columnType === Types::DATETIME_IMMUTABLE,
            'array' => $columnType === Types::JSON,
            'bool' => $columnType === Types::BOOLEAN,
            'float' => $columnType === Types::FLOAT,
            'int' => $columnType === Types::INTEGER,
            'string' => $columnType === Types::STRING,
            default => false,
        };
    }

    public static function getPropertyTypeForColumn(string $columnType): ?string
    {
        return match ($columnType) {
            Types::STRING, Types::TEXT, Types::GUID, Types::BIGINT, Types::DECIMAL => 'string',
            Types::ARRAY, Types::SIMPLE_ARRAY, Types::JSON => 'array',
            Types::BOOLEAN => 'bool',
            Types::INTEGER, Types::SMALLINT => 'int',
            Types::FLOAT => 'float',
            Types::DATETIME_MUTABLE, Types::DATETIMETZ_MUTABLE, Types::DATE_MUTABLE, Types::TIME_MUTABLE => '\\' . \DateTimeInterface::class,
            Types::DATETIME_IMMUTABLE, Types::DATETIMETZ_IMMUTABLE, Types::DATE_IMMUTABLE, Types::TIME_IMMUTABLE => '\\' . \DateTimeImmutable::class,
            Types::DATEINTERVAL => '\\' . \DateInterval::class,
            Types::OBJECT => 'object',
            default => null,
        };
    }

    /**
     * Given the string "column type", this returns the "Types::STRING" constant.
     *
     * This is, effectively, a reverse lookup: given the final string, give us
     * the constant to be used in the generated code.
     */
    public static function getTypeConstant(string $columnType): ?string
    {
        $reflection = new \ReflectionClass(Types::class);
        $constants = array_flip($reflection->getConstants());

        if (!isset($constants[$columnType])) {
            return null;
        }

        return sprintf('Types::%s', $constants[$columnType]);
    }
}
