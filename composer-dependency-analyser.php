<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->disableComposerAutoloadPathScan()
    ->setFileExtensions(['php'])
    ->addPathToScan(__DIR__ . '/config', isDev: false)
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    // Debug module under development, not used in production
    ->ignoreErrorsOnPath(__DIR__ . '/src/Debug', [ErrorType::DEV_DEPENDENCY_IN_PROD, ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPath(__DIR__ . '/tests/Debug', [ErrorType::SHADOW_DEPENDENCY])
    // Virtual meta-package that only declares a contract, fulfilled via another package's `provide`.
    ->ignoreErrorsOnPackages(['yiisoft/router-implementation'], [ErrorType::UNUSED_DEPENDENCY])
    // yiisoft/hydrator is optional (see `suggest` in composer.json), only needed for the `RouteArgument` attribute.
    ->ignoreErrorsOnPackage('yiisoft/hydrator', [ErrorType::DEV_DEPENDENCY_IN_PROD]);
