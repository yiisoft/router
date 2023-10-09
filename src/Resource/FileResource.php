<?php

declare(strict_types=1);

namespace Yiisoft\Router\Resource;

/**
 * A file resource represents routes from a file or directory of files.
 */
final class FileResource implements ResourceInterface
{
    public function __construct(private string $file, private array $scope = [])
    {
    }

    public function getRoutes(): array
    {
        $scopeRequire = static function (string $file, array $scope): array {
            extract($scope, EXTR_SKIP);

            return require $file;
        };
        if (!file_exists($this->file)) {
            throw new \RuntimeException();
        }
        if (is_dir($this->file) && !is_file($this->file)) {
            $routes = [];
            $files = new \CallbackFilterIterator(
                new \FilesystemIterator(
                    $this->file,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS
                ),
                fn(\SplFileInfo $fileInfo) => $fileInfo->isFile() && $fileInfo->getExtension() === 'php'
            );
            /** @var \SplFileInfo[] $files */
            foreach ($files as $file) {
                $fileRoutes = $scopeRequire($file->getRealPath(), $this->scope);
                if (is_array($fileRoutes)) {
                    array_push(
                        $routes,
                        ...$fileRoutes
                    );
                }
            }
            return $routes;
        }

        return $scopeRequire($this->file, $this->scope);
    }
}
