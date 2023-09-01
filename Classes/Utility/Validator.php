<?php

declare(strict_types=1);

namespace Mirko\T3maker\Utility;

use Mirko\T3maker\Validator\ClassValidator;
use RuntimeException;

final class Validator
{
    public static function validateClassName(string $className, string $errorMessage = ''): string
    {
        // remove potential opening slash so we don't match on it
        $pieces = explode('\\', ltrim($className, '\\'));
        $shortClassName = StringUtility::getShortClassName($className);

        $reservedKeywords = ClassValidator::RESERVED_WORDS;

        foreach ($pieces as $piece) {
            if (!mb_check_encoding($piece, 'UTF-8')) {
                $errorMessage = $errorMessage ?: sprintf('"%s" is not a UTF-8-encoded string.', $piece);

                throw new RuntimeException($errorMessage);
            }

            if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $piece)) {
                $errorMessage = $errorMessage ?: sprintf(
                    '"%s" is not valid as a PHP class name (it must start with a letter or underscore, followed by any number of letters, numbers, or underscores)',
                    $className
                );

                throw new RuntimeException($errorMessage);
            }

            if (\in_array(strtolower($shortClassName), $reservedKeywords, true)) {
                throw new RuntimeException(
                    sprintf('"%s" is a reserved keyword and thus cannot be used as class name in PHP.', $shortClassName)
                );
            }
        }

        // return original class name
        return $className;
    }

    public static function classExists(string $className, string $errorMessage = ''): string
    {
        self::notBlank($className);

        if (!class_exists($className)) {
            $errorMessage = $errorMessage ?: sprintf(
                'Class "%s" doesn\'t exist; please enter an existing full class name.',
                $className
            );

            throw new RuntimeException($errorMessage);
        }

        return $className;
    }

    public static function entityExists(string $className = null, array $entities = []): string
    {
        self::notBlank($className);

        if (empty($entities)) {
            throw new RuntimeException(
                'There are no registered entities; please create an entity before using this command.'
            );
        }

        if (str_starts_with($className, '\\')) {
            self::classExists(
                $className,
                sprintf('Entity "%s" doesn\'t exist; please enter an existing one or create a new one.', $className)
            );
        }

        if (!\in_array($className, $entities, true)) {
            throw new RuntimeException(
                sprintf('Entity "%s" doesn\'t exist; please enter an existing one or create a new one.', $className)
            );
        }

        return $className;
    }

    public static function notBlank(string $value = null): string
    {
        if ($value === null || $value === '') {
            throw new RuntimeException('This value cannot be blank.');
        }

        return $value;
    }
}
