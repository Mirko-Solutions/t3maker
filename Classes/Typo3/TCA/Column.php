<?php

declare(strict_types=1);

namespace Mirko\T3maker\Typo3\TCA;

class Column
{
    private string $label = '';

    private int $exclude = 0;

    private string $description = '';

    private Config|null $config = null;
}
