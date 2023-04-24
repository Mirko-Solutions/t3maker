<?php

namespace Mirko\T3maker\Utility;

use Mirko\T3maker\Validator\ClassValidator;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 *
 * @internal
 */
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

                throw new RuntimeCommandException($errorMessage);
            }

            if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $piece)) {
                $errorMessage = $errorMessage ?: sprintf(
                    '"%s" is not valid as a PHP class name (it must start with a letter or underscore, followed by any number of letters, numbers, or underscores)',
                    $className
                );

                throw new RuntimeCommandException($errorMessage);
            }

            if (\in_array(strtolower($shortClassName), $reservedKeywords, true)) {
                throw new RuntimeCommandException(
                    sprintf('"%s" is a reserved keyword and thus cannot be used as class name in PHP.', $shortClassName)
                );
            }
        }

        // return original class name
        return $className;
    }

    public static function validateLength($length)
    {
        if (!$length) {
            return $length;
        }

        $result = filter_var(
            $length,
            \FILTER_VALIDATE_INT,
            [
                'options' => ['min_range' => 1],
            ]
        );

        if (false === $result) {
            throw new RuntimeCommandException(sprintf('Invalid length "%s".', $length));
        }

        return $result;
    }

    public static function validatePrecision($precision)
    {
        if (!$precision) {
            return $precision;
        }

        $result = filter_var(
            $precision,
            \FILTER_VALIDATE_INT,
            [
                'options' => ['min_range' => 1, 'max_range' => 65],
            ]
        );

        if (false === $result) {
            throw new RuntimeCommandException(sprintf('Invalid precision "%s".', $precision));
        }

        return $result;
    }

    public static function validateScale($scale)
    {
        if (!$scale) {
            return $scale;
        }

        $result = filter_var(
            $scale,
            \FILTER_VALIDATE_INT,
            [
                'options' => ['min_range' => 0, 'max_range' => 30],
            ]
        );

        if (false === $result) {
            throw new RuntimeCommandException(sprintf('Invalid scale "%s".', $scale));
        }

        return $result;
    }

    public static function validateBoolean($value)
    {
        if ('yes' === $value) {
            return true;
        }

        if ('no' === $value) {
            return false;
        }

        if (null === $valueAsBool = filter_var($value, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE)) {
            throw new RuntimeCommandException(sprintf('Invalid bool value "%s".', $value));
        }

        return $valueAsBool;
    }

    public static function validatePropertyName(string $name): string
    {
        // check for valid PHP variable name
        if (!StringUtility::isValidPhpVariableName($name)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid PHP property name.', $name));
        }

        return $name;
    }

    public static function validateEmailAddress(?string $email): string
    {
        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeCommandException(sprintf('"%s" is not a valid email address.', $email));
        }

        return $email;
    }

    public static function existsOrNull(?string $className = null, array $entities = []): ?string
    {
        if (null !== $className) {
            self::validateClassName($className);

            if (str_starts_with($className, '\\')) {
                self::classExists($className);
            } else {
                self::entityExists($className, $entities);
            }
        }

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

            throw new RuntimeCommandException($errorMessage);
        }

        return $className;
    }

    public static function entityExists(string $className = null, array $entities = []): string
    {
        self::notBlank($className);

        if (empty($entities)) {
            throw new RuntimeCommandException(
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
            throw new RuntimeCommandException(
                sprintf('Entity "%s" doesn\'t exist; please enter an existing one or create a new one.', $className)
            );
        }

        return $className;
    }

    public static function classDoesNotExist($className): string
    {
        self::notBlank($className);

        if (class_exists($className)) {
            throw new RuntimeCommandException(sprintf('Class "%s" already exists.', $className));
        }

        return $className;
    }
}
