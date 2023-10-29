<?php

declare(strict_types=1);

namespace Mirko\T3maker\Utility;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\VarExporter\VarExporter;

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
        return '<?php return ' . VarExporter::export($this->tcaConfiguration) . ';';
    }

    public function updateColumnConfig(string $columnName, array $config): void
    {
        if (!array_key_exists('config', $this->tcaConfiguration['columns'][$columnName])) {
            $this->tcaConfiguration['columns'][$columnName]['config'] = $config;
            return;
        }

        if (
            !array_key_exists('type', $this->tcaConfiguration['columns'][$columnName]['config']) ||
            $this->tcaConfiguration['columns'][$columnName]['config']['type'] !== $config['type']
        ) {
            $this->tcaConfiguration['columns'][$columnName]['config'] = $config;
            return;
        }

        $this->recursiveUpdate($this->tcaConfiguration['columns'][$columnName]['config'], $config);
    }

    public function getTcaColumns(): array
    {
        return $this->tcaConfiguration['columns'] ?? [];
    }

    public function propertyExists(string $propertyName): bool
    {
        if (array_key_exists($propertyName, $this->getTcaColumns())) {
            return true;
        }

        return false;
    }

    private function recursiveUpdate(array &$target, array $source): void
    {
        foreach ($source as $key => $value) {
            if (is_array($value)) {
                if (!isset($target[$key])) {
                    $target[$key] = [];
                }
                $this->recursiveUpdate($target[$key], $value);
            } else {
                $target[$key] = $value;
            }
        }
    }
}
