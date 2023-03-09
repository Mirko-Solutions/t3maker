<?= "<?php\n"; ?>

defined('TYPO3_MODE') or die();

return [
    'ctrl' => [
        'title' => '<?= $title; ?>',
        'label' => '<?= $label; ?>',
        //add replace Icon
        'iconfile' => '',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
    ],
    'interface' => [
        'showRecordFieldList' => '<?= $interfaceShowRecordFieldList; ?>',
    ],
    'types' => [
        '1' => ['showitem' => '<?= $typesShowItem; ?>'],
    ],
    'palettes' => [
        '1' => ['showitem' => '<?= $palettesShowItem; ?>'],
    ],
    'columns' => [
        <?= $columns; ?>
    ],
];

