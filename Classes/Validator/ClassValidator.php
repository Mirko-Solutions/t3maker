<?php

declare(strict_types=1);

namespace Mirko\T3maker\Validator;

use Mirko\T3maker\Utility\StringUtility;

final class ClassValidator
{
    public const RESERVED_WORDS = ['__halt_compiler', 'abstract', 'and', 'array',
        'as', 'break', 'callable', 'case', 'catch', 'class',
        'clone', 'const', 'continue', 'declare', 'default', 'die', 'do',
        'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor',
        'endforeach', 'endif', 'endswitch', 'endwhile', 'eval',
        'exit', 'extends', 'final', 'finally', 'for', 'foreach', 'function',
        'global', 'goto', 'if', 'implements', 'include',
        'include_once', 'instanceof', 'insteadof', 'interface', 'isset',
        'list', 'namespace', 'new', 'or', 'print', 'private',
        'protected', 'public', 'require', 'require_once', 'return',
        'static', 'switch', 'throw', 'trait', 'try', 'unset',
        'use', 'var', 'while', 'xor', 'yield',
        'int', 'float', 'bool', 'string', 'true', 'false', 'null', 'void',
        'iterable', 'object', '__file__', '__line__', '__dir__', '__function__', '__class__',
        '__method__', '__namespace__', '__trait__', 'self', 'parent',
    ];

    /**
     * @param string $className
     * @param string $errorMessage
     * @return string
     */
    public static function validateClassName(string $className, string $errorMessage = ''): string
    {
        // remove potential opening slash so we don't match on it
        $pieces = explode('\\', ltrim($className, '\\'));
        $shortClassName = StringUtility::getShortClassName($className);

        foreach ($pieces as $piece) {
            if (!mb_check_encoding($piece, 'UTF-8')) {
                $errorMessage = $errorMessage ?: sprintf('"%s" is not a UTF-8-encoded string.', $piece);

                throw new \RuntimeException($errorMessage);
            }

            if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $piece)) {
                $errorMessage = $errorMessage ?: sprintf(
                    '"%s" is not valid as a PHP class name (it must start with a letter or underscore, followed by any number of letters, numbers, or underscores)',
                    $className
                );

                throw new \RuntimeException($errorMessage);
            }

            if (\in_array(strtolower($shortClassName), self::RESERVED_WORDS, true)) {
                throw new \RuntimeException(
                    sprintf('"%s" is a reserved keyword and thus cannot be used as class name in PHP.', $shortClassName)
                );
            }
        }

        // return original class name
        return $className;
    }

    public static function notEmpty(string $value = null): string
    {
        if (null === $value || '' === $value) {
            throw new \RuntimeException('This value cannot be empty.');
        }

        return $value;
    }
}