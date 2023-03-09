<?php

declare(strict_types=1);


namespace Mirko\T3maker\Doctrine;
//TODO implement
/**
 * @internal
 */
final class EntityRelation
{
    public const MANY_TO_ONE = 'ManyToOne';
    public const ONE_TO_MANY = 'OneToMany';
    public const MANY_TO_MANY = 'ManyToMany';
    public const ONE_TO_ONE = 'OneToOne';

    private bool $isNullable = false;
    private bool $isSelfReferencing = false;

    public function __construct(
        private string $type,
        private string $owningClass,
        private string $inverseClass,
    ) {
        if (!\in_array($type, self::getValidRelationTypes())) {
            throw new \Exception(sprintf('Invalid relation type "%s"', $type));
        }

        if (self::ONE_TO_MANY === $type) {
            throw new \Exception('Use ManyToOne instead of OneToMany');
        }

        $this->isSelfReferencing = $owningClass === $inverseClass;
    }

    public static function getValidRelationTypes(): array
    {
        return [
            self::MANY_TO_ONE,
            self::ONE_TO_MANY,
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
}