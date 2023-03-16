<?php

declare(strict_types=1);


namespace Mirko\T3maker\Utility;

use PhpParser\Parser;
use PhpParser\Builder;
use PhpParser\BuilderHelpers;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use Symfony\Component\Console\Style\SymfonyStyle;

class TCASourceManipulator
{
    private ?SymfonyStyle $io = null;

    private array $tcaConfiguration = [];

    public function __construct(
        private string $filePath,
        private bool $overwrite = false,
    ) {
        $this->setSourceCode($filePath);
    }

    public function setIo(SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    private function setSourceCode(string $filePath): void
    {
        $this->tcaConfiguration = require $filePath;
    }

    public function getSourceCode(): string
    {
        return '<?php return ' . var_export($this->tcaConfiguration, true) . ';';
    }

    public function updateColumnConfig(string $columnName, array $config): void
    {
        $this->tcaConfiguration['columns'][$columnName] = $config;
    }

    private function getTcaColumns(): array
    {
        return $this->tcaConfiguration['columns'] ?? [];
    }

    private function propertyExists(string $propertyName): bool
    {
        if (array_key_exists($propertyName, $this->getTcaColumns())) {
            return true;
        }

        return false;
    }
}