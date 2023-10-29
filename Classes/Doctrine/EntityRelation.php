<?php

declare(strict_types=1);

namespace Mirko\T3maker\Doctrine;

use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * @internal
 */
final class EntityRelation
{
    public const ONE_TO_MANY = 'OneToMany';
    public const MANY_TO_ONE = 'ManyToOne';
    public const MANY_TO_MANY = 'ManyToMany';
    public const ONE_TO_ONE = 'OneToOne';

    private $owningProperty;
    private $inverseProperty;
    private bool $isNullable = false;
    private bool $isSelfReferencing;
    private bool $orphanRemoval = false;
    private bool $mapInverseRelation = true;

    public function __construct(
        private string $type,
        private string $owningClass,
        private string $inverseClass,
    ) {
        if (!\in_array($type, self::getValidRelationTypes())) {
            throw new Exception(sprintf('Invalid relation type "%s"', $type));
        }

        $this->isSelfReferencing = $owningClass === $inverseClass;
    }

    public static function getValidRelationTypes(): array
    {
        return [
            self::ONE_TO_MANY,
            self::MANY_TO_ONE,
            self::MANY_TO_MANY,
            self::ONE_TO_ONE,
        ];
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function setOwningProperty(string $owningProperty): void
    {
        $this->owningProperty = $owningProperty;
    }

    public function setInverseProperty(string $inverseProperty): void
    {
        if (!$this->mapInverseRelation) {
            throw new Exception('Cannot call setInverseProperty() when the inverse relation will not be mapped.');
        }

        $this->inverseProperty = $inverseProperty;
    }

    public function setIsNullable(bool $isNullable): void
    {
        $this->isNullable = $isNullable;
    }

    public function setOrphanRemoval(bool $orphanRemoval): void
    {
        $this->orphanRemoval = $orphanRemoval;
    }

    public function getOwningRelation(): RelationManyToMany|RelationOneToOne|RelationManyToOne
    {
        return match ($this->getType()) {
            self::MANY_TO_ONE => (new RelationManyToOne(
                propertyName: $this->owningProperty,
                targetClassName: $this->inverseClass,
                targetPropertyName: $this->inverseProperty,
                isSelfReferencing: $this->isSelfReferencing,
                mapInverseRelation: $this->mapInverseRelation,
                isOwning: true,
                isNullable: $this->isNullable,
            )),
            self::MANY_TO_MANY => (new RelationManyToMany(
                propertyName: $this->owningProperty,
                targetClassName: $this->inverseClass,
                targetPropertyName: $this->inverseProperty,
                isSelfReferencing: $this->isSelfReferencing,
                mapInverseRelation: $this->mapInverseRelation,
                isOwning: true,
            )),
            self::ONE_TO_ONE => (new RelationOneToOne(
                propertyName: $this->owningProperty,
                targetClassName: $this->inverseClass,
                targetPropertyName: $this->inverseProperty,
                isSelfReferencing: $this->isSelfReferencing,
                mapInverseRelation: $this->mapInverseRelation,
                isOwning: true,
                isNullable: $this->isNullable,
            )),
            default => throw new InvalidArgumentException('Invalid type'),
        };
    }

    public function getInverseRelation(): RelationManyToMany|RelationOneToOne|RelationOneToMany
    {
        return match ($this->getType()) {
            self::MANY_TO_ONE => (new RelationOneToMany(
                propertyName: $this->inverseProperty,
                targetClassName: $this->owningClass,
                targetPropertyName: $this->owningProperty,
                isSelfReferencing: $this->isSelfReferencing,
                orphanRemoval: $this->orphanRemoval,
            )),
            self::MANY_TO_MANY => (new RelationManyToMany(
                propertyName: $this->inverseProperty,
                targetClassName: $this->owningClass,
                targetPropertyName: $this->owningProperty,
                isSelfReferencing: $this->isSelfReferencing
            )),
            self::ONE_TO_ONE => (new RelationOneToOne(
                propertyName: $this->inverseProperty,
                targetClassName: $this->owningClass,
                targetPropertyName: $this->owningProperty,
                isSelfReferencing: $this->isSelfReferencing,
                isNullable: $this->isNullable,
            )),
            default => throw new InvalidArgumentException('Invalid type'),
        };
    }

    public function getOwningClass(): string
    {
        return $this->owningClass;
    }

    public function getInverseClass(): string
    {
        return $this->inverseClass;
    }

    public function getOwningProperty(): string
    {
        return $this->owningProperty;
    }

    public function getInverseProperty(): string
    {
        return $this->inverseProperty;
    }

    public function isSelfReferencing(): bool
    {
        return $this->isSelfReferencing;
    }

    public function getMapInverseRelation(): bool
    {
        return $this->mapInverseRelation;
    }

    public function setMapInverseRelation(bool $mapInverseRelation): void
    {
        if ($mapInverseRelation && $this->inverseProperty) {
            throw new RuntimeException(
                'Cannot set setMapInverseRelation() to true when the inverse relation property is set.'
            );
        }

        $this->mapInverseRelation = $mapInverseRelation;
    }
}
