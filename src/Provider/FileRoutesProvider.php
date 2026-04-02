<?php

declare(strict_types=1);

namespace Yiisoft\Router\Provider;

use Closure;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use CallbackFilterIterator;
use FilesystemIterator;
use RuntimeException;
use SplFileInfo;

use function is_array;
use function iterator_to_array;

use const EXTR_SKIP;

/**
 * A file provider provides routes from a file or directory of files.
 */
final class FileRoutesProvider implements RoutesProviderInterface
{
    public function __construct(private readonly string $file, private readonly array $scope = []) {}

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
            throw new RuntimeException(
                'Failed to provide routes from "' . $this->file . '". File or directory not found.',
            );
        }
        /** @infection-ignore-all Equivalent: is_dir implies !is_file for valid paths after file_exists check */
        if (is_dir($this->file) && !is_file($this->file)) {
            $directoryRoutes = [];
            $files = new CallbackFilterIterator(
                new FilesystemIterator(
                    $this->file,
                    /** @infection-ignore-all Bitwise flags; CallbackFilterIterator already filters by extension */
                    FilesystemIterator::SKIP_DOTS,
                ),
                fn(SplFileInfo $fileInfo) => $fileInfo->isFile() && $fileInfo->getExtension() === 'php',
            );
            $files = iterator_to_array($files, false);
            /** @var SplFileInfo[] $files */
            usort($files, static fn(SplFileInfo $a, SplFileInfo $b) => $a->getFilename() <=> $b->getFilename());
            foreach ($files as $file) {
                $realPath = $file->getRealPath();
                if ($realPath === false) {
                    continue;
                }
                /** @var mixed $fileRoutes */
                $fileRoutes = $scopeRequire($realPath, $this->scope);
                if (is_array($fileRoutes) && $this->areRoutesValid($fileRoutes)) {
                    array_push(
                        $directoryRoutes,
                        ...$fileRoutes,
                    );
                }
            }
            return $directoryRoutes;
        }

        /** @var mixed $routes */
        $routes = $scopeRequire($this->file, $this->scope);
        if (is_array($routes) && $this->areRoutesValid($routes)) {
            return $routes;
        }

        return [];
    }

    /**
     * @psalm-assert-if-true Route[]|Group[] $routes
     */
    private function areRoutesValid(array $routes): bool
    {
        foreach ($routes as $route) {
            if (!$route instanceof Route && !$route instanceof Group) {
                return false;
            }
        }
        return true;
    }
}
