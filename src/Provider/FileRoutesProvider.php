<?php

declare(strict_types=1);

namespace Yiisoft\Router\Provider;

use Closure;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

/**
 * A file provider provides routes from a file or directory of files.
 */
final class FileRoutesProvider implements RoutesProviderInterface
{
    public function __construct(private string $file, private array $scope = [])
    {
    }

    public function getRoutes(): array
    {
        /** @var Closure $scopeRequire */
        $scopeRequire = Closure::bind(static function (string $file, array $scope): mixed {
            extract($scope, EXTR_SKIP);
            /**
             * @psalm-suppress UnresolvableInclude
             */
            return require $file;
        }, null);
        if (!file_exists($this->file)) {
            throw new \RuntimeException(
                'Failed to provide routes from "' . $this->file . '". File or directory not found.'
            );
        }
        if (is_dir($this->file) && !is_file($this->file)) {
            $directoryRoutes = [];
            $files = new \CallbackFilterIterator(
                new \FilesystemIterator(
                    $this->file,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS
                ),
                fn (\SplFileInfo $fileInfo) => $fileInfo->isFile() && $fileInfo->getExtension() === 'php'
            );
            /** @var \SplFileInfo[] $files */
            foreach ($files as $file) {
                /** @var mixed $fileRoutes */
                $fileRoutes = $scopeRequire($file->getRealPath(), $this->scope);
                if (is_array($fileRoutes) && $this->isRoutesAreValid($fileRoutes)) {
                    array_push(
                        $directoryRoutes,
                        ...$fileRoutes
                    );
                }
            }
            return $directoryRoutes;
        }

        /** @var mixed $routes */
        $routes = $scopeRequire($this->file, $this->scope);
        if (is_array($routes) && $this->isRoutesAreValid($routes)) {
            return $routes;
        }

        return [];
    }

    /**
     * @psalm-assert-if-true Route[]|Group[] $routes
     */
    private function isRoutesAreValid(array $routes): bool
    {
        foreach ($routes as $route) {
            if (
                !is_a($route, Route::class, true) && !is_a($route, Group::class, true)
            ) {
                return false;
            }
        }
        return true;
    }
}
