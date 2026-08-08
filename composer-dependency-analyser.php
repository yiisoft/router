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
    // Debug module is an optional integration with `yiisoft/yii-debug`, only used when it is installed.
    ->ignoreErrorsOnPackages(['symfony/console', 'yiisoft/var-dumper'], [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackages(['psr/container', 'yiisoft/hydrator', 'yiisoft/yii-debug'], [ErrorType::DEV_DEPENDENCY_IN_PROD])
    // Virtual meta-package that only declares a contract, fulfilled via another package's `provide`.
    ->ignoreErrorsOnPackages(['yiisoft/router-implementation'], [ErrorType::UNUSED_DEPENDENCY]);
