<?php

declare(strict_types=1);

namespace Mirko\T3maker\Generator;

use Mirko\T3maker\FileManager;
use Mirko\T3maker\Utility\ClassDetails;
use Mirko\T3maker\Utility\PackageDetails;
use Mirko\T3maker\Utility\StringUtility;
use Mirko\T3maker\Utility\Typo3Utility;
use Mirko\T3maker\Validator\ClassValidator;

final class Generator
{
    private array $pendingOperations = [];
    private string $namespacePrefix = '';

    public function __construct(private FileManager $fileManager)
    {
    }

    public function createClassNameDetails(
        string $name,
        string $namespacePrefix,
        string $suffix = '',
        string $validationErrorMessage = ''
    ): ClassDetails {
        $this->namespacePrefix = $fullNamespacePrefix = $namespacePrefix;
        if ('\\' === $name[0]) {
            // class is already "absolute" - leave it alone (but strip opening \)
            $className = substr($name, 1);
        } else {
            $className = rtrim($fullNamespacePrefix, '\\') . '\\' . StringUtility::asClassName($name, $suffix);
        }

        ClassValidator::validateClassName($className, $validationErrorMessage);

        return new ClassDetails($className, $fullNamespacePrefix, $suffix);
    }

    /**
     * Generate a new file for a class from a template.
     *
     * @param string $className The fully-qualified class name
     * @param string $templateName Template name in Resources/skeleton to use
     * @param array $variables Array of variables to pass to the template
     *
     * @return string The path where the file will be created
     *
     * @throws \Exception
     */
    public function generateClass(PackageDetails $package, string $className, string $templateName, array $variables = []): string
    {
        $this->fileManager->setRootDirectory(Typo3Utility::getExtensionPath($package->getName()));
        $targetPath = $this->fileManager->getRelativePathForFutureClass($package, $className);

        if (null === $targetPath) {
            throw new \LogicException(
                sprintf(
                    'Could not determine where to locate the new class "%s", maybe try with a full namespace like "\\My\\Full\\Namespace\\%s"',
                    $className,
                    StringUtility::getShortClassName($className)
                )
            );
        }

        $variables = array_merge(
            $variables,
            [
                'class_name' => StringUtility::getShortClassName($className),
                'namespace' => StringUtility::getNamespace($className),
            ]
        );

        $this->addOperation($targetPath, $templateName, $variables);

        return $targetPath;
    }

    public function generateFile(PackageDetails $package, string $fileName, string $templateName, array $variables = [])
    {
        $this->fileManager->setRootDirectory(Typo3Utility::getExtensionPath($package->getName()));
        $targetPath = $this->fileManager->absolutizePath($fileName);

        $this->addOperation($targetPath, $templateName, $variables);

        return $targetPath;
    }

    private function addOperation(string $targetPath, string $templateName, array $variables): void
    {
        if ($this->fileManager->fileExists($targetPath)) {
            throw new \RuntimeException(
                sprintf(
                    'The file "%s" can\'t be generated because it already exists.',
                    $this->fileManager->relativizePath($targetPath)
                )
            );
        }

        $variables['relative_path'] = $this->fileManager->relativizePath($targetPath);

        $templatePath = $templateName;
        if (!file_exists($templatePath)) {
            $templatePath = Typo3Utility::getExtensionPath('t3maker') . 'Resources/skeleton/' . $templateName;

            if (!file_exists($templatePath)) {
                throw new \Exception(sprintf('Cannot find template "%s"', $templateName));
            }
        }

        $this->pendingOperations[$targetPath] = [
            'template' => $templatePath,
            'variables' => $variables,
        ];
    }

    /**
     * Actually writes and file changes that are pending.
     *
     * @return void
     */
    public function writeChanges()
    {
        foreach ($this->pendingOperations as $targetPath => $templateData) {
            if (isset($templateData['contents'])) {
                $this->fileManager->dumpFile($targetPath, $templateData['contents']);

                continue;
            }

            $this->fileManager->dumpFile(
                $targetPath,
                $this->getFileContentsForPendingOperation($targetPath, $templateData)
            );
        }

        $this->pendingOperations = [];
    }

    public function hasPendingOperations(): bool
    {
        return !empty($this->pendingOperations);
    }

    public function getFileContentsForPendingOperation(string $targetPath): string
    {
        if (!isset($this->pendingOperations[$targetPath])) {
            throw new \RuntimeException(sprintf('File "%s" is not in the Generator\'s pending operations', $targetPath));
        }

        $templatePath = $this->pendingOperations[$targetPath]['template'];
        $parameters = $this->pendingOperations[$targetPath]['variables'];

        $templateParameters = array_merge($parameters, [
            'relative_path' => $this->fileManager->relativizePath($targetPath),
        ]);

        return $this->fileManager->parseTemplate($templatePath, $templateParameters);
    }
}