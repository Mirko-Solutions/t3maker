<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Typo3 Maker',
    'description' => 'TYPO3 Maker helps you create empty commands, controllers, models and more so you can forget about writing boilerplate code.',
    'category' => 'module',
    'version' => '0.0.1',
    'state' => 'beta',
    'author' => 'Mirko (developer: Stanislav Nazar)',
    'author_email' => 'support@mirko.in.ua',
    'author_company' => 'Mirko',
    'constraints' => [
        'depends' => [
            'php' => '8.0.*-8.1.*',
            'typo3' => '11.0.0-12.9.99',
        ],
    ],
];
