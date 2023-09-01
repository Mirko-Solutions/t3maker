<?php

declare(strict_types = 1);

use TYPO3\CodingStandards\CsFixerConfig;

$config = CsFixerConfig::create();
$rules = $config->getRules();

// Removing outdated rules and establishing alternative ones
unset($rules['braces'], $rules['function_typehint_space']);
$rules += [
    'type_declaration_spaces' => true,
    'single_space_around_construct' => true,
    'control_structure_braces' => true,
    'control_structure_continuation_position' => true,
    'declare_parentheses' => true,
    'no_multiple_statements_per_line' => true,
    'curly_braces_position' => true,
    'statement_indentation' => true,
    'no_extra_blank_lines' => true
];

$config->setRules($rules);
$config->getFinder()->in('Classes')->in('Configuration');
return $config;
