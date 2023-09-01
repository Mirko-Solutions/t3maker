<?php

declare(strict_types=1);

namespace Mirko\T3maker;

use Exception;
use InvalidArgumentException;
use Mirko\T3maker\Utility\PackageDetails;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class FileManager
{
    private ?SymfonyStyle $io = null;

    public function __construct(
        private Filesystem $fs,
        private string $rootDirectory = '',
    ) {
    }

    public function setIO(SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    public function setRootDirectory(string $rootDirectory): void
    {
        $this->rootDirectory = rtrim($this->realPath($this->normalizeSlashes($rootDirectory)), '/');
    }

    public function parseTemplate(string $templatePath, array $parameters): string
    {
        ob_start();
        extract($parameters, \EXTR_SKIP);
        include $templatePath;

        return ob_get_clean();
    }

    public function dumpFile(string $filename, string $content): void
    {
        $absolutePath = $this->absolutizePath($filename);
        $newFile = !$this->fileExists($filename);
        $existingContent = $newFile ? '' : file_get_contents($absolutePath);

        $comment = $newFile ? '<fg=blue>created</>' : '<fg=yellow>updated</>';
        if ($existingContent === $content) {
            $comment = '<fg=green>no change</>';
        }

        $this->fs->dumpFile($absolutePath, $content);
        $relativePath = $this->relativizePath($filename);

        $this->io?->comment(sprintf(
            '%s: %s',
            $comment,
            $relativePath
        ));
    }

    public function fileExists($path): bool
    {
        return file_exists($this->absolutizePath($path));
    }

    /**
     * Attempts to make the path relative to the root directory.
     *
     * @throws Exception
     */
    public function relativizePath(string $absolutePath): string
    {
        $absolutePath = $this->normalizeSlashes($absolutePath);

        // see if the path is even in the root
        if (!str_contains($absolutePath, $this->rootDirectory)) {
            return $absolutePath;
        }

        $absolutePath = $this->realPath($absolutePath);

        // str_replace but only the first occurrence
        $relativePath = ltrim(implode('', explode($this->rootDirectory, $absolutePath, 2)), '/');
        if (str_starts_with($relativePath, './')) {
            $relativePath = substr($relativePath, 2);
        }

        return is_dir($absolutePath) ? rtrim($relativePath, '/') . '/' : $relativePath;
    }

    public function getFileContents(string $path): string
    {
        if (!$this->fileExists($path)) {
            throw new InvalidArgumentException(sprintf('Cannot find file "%s"', $path));
        }

        return file_get_contents($this->absolutizePath($path));
    }

    public function absolutizePath($path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // support windows drive paths: C:\ or C:/
        if (strpos($path, ':\\') === 1 || strpos($path, ':/') === 1) {
            return $path;
        }

        return sprintf('%s/%s', $this->rootDirectory, $path);
    }

    /**
     * @throws Exception
     */
    public function getRelativePathForFutureClass(PackageDetails $packageDetails, string $className): ?string
    {
        $path = $packageDetails->getComposerNamespaces()[$packageDetails->getNamespace()] . str_replace(
            '\\',
            '/',
            substr($className, \strlen($packageDetails->getNamespace()))
        ) . '.php';

        return $this->relativizePath($path);
    }

    /**
     * Resolve '../' in paths (like real_path), but for non-existent files.
     *
     * @throws Exception
     */
    private function realPath(string $absolutePath): string
    {
        $finalParts = [];
        $currentIndex = -1;

        $absolutePath = $this->normalizeSlashes($absolutePath);
        foreach (explode('/', $absolutePath) as $pathPart) {
            if ($pathPart === '..') {
                // we need to remove the previous entry
                if ($currentIndex === -1) {
                    throw new Exception(
                        sprintf('Problem making path relative - is the path "%s" absolute?', $absolutePath)
                    );
                }

                unset($finalParts[$currentIndex]);
                --$currentIndex;

                continue;
            }

            $finalParts[] = $pathPart;
            ++$currentIndex;
        }

        $finalPath = implode('/', $finalParts);
        // Normalize: // => /
        // Normalize: /./ => /
        return str_replace(['//', '/./'], '/', $finalPath);
    }

    private function normalizeSlashes(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    public function isPathInVendor(string $path): bool
    {
        return str_starts_with(
            $this->normalizeSlashes($path),
            $this->normalizeSlashes($this->rootDirectory . '/vendor/')
        );
    }
}
