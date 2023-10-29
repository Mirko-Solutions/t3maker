<?php

declare(strict_types = 1);

use TYPO3\CodingStandards\CsFixerConfig;

$config = CsFixerConfig::create();
$rules = $config->getRules();

// Removing deprecated rules and establishing alternative ones
unset($rules['braces'], $rules['function_typehint_space']);
$rules += [
    '@PER:risky' => true,
    '@PHP81Migration' => true,
    'control_structure_braces' => true,
    'control_structure_continuation_position' => true,
    'curly_braces_position' => true,
    'declare_parentheses' => true,
    'fully_qualified_strict_types' => true,
    'global_namespace_import' => [
        'import_classes' => true,
        'import_constants' => false,
        'import_functions' => false,
    ],
    'no_extra_blank_lines' => true,
    'no_multiple_statements_per_line' => true,
    'no_unneeded_import_alias' => true,
    'ordered_imports' => [
        'imports_order' => ['class', 'function', 'const'],
        'sort_algorithm' => 'alpha',
    ],
    'phpdoc_align' => true,
    'phpdoc_annotation_without_dot' => true,
    'phpdoc_indent' => true,
    'phpdoc_inline_tag_normalizer' => true,
    'phpdoc_line_span' => true,
    'phpdoc_no_useless_inheritdoc' => true,
    'phpdoc_order' => true,
    'phpdoc_order_by_value' => true,
    'phpdoc_separation' => true,
    'phpdoc_single_line_var_spacing' => true,
    'phpdoc_summary' => true,
    'phpdoc_tag_casing' => true,
    'phpdoc_tag_type' => true,
    'phpdoc_to_comment' => [
        'ignored_tags' => [
            'phpstan-ignore-line',
            'phpstan-ignore-next-line',
            'todo',
        ],
    ],
    'phpdoc_trim_consecutive_blank_line_separation' => true,
    'phpdoc_types_order' => [
        'null_adjustment' => 'always_last',
        'sort_algorithm' => 'alpha',
    ],
    'phpdoc_var_annotation_correct_order' => true,
    'phpdoc_var_without_name' => true,
    'self_accessor' => true,
    'single_space_around_construct' => true,
    'statement_indentation' => true,
    'type_declaration_spaces' => true,
];
$config->setRules($rules);
$config->getFinder()->in('Classes')->in('Configuration');
return $config;
