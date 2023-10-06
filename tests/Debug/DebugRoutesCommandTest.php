<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Debug;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Router\Debug\DebugRoutesCommand;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Yii\Debug\Debugger;
use Yiisoft\Yii\Debug\DebuggerIdGenerator;
use Yiisoft\Yii\Debug\Storage\StorageInterface;

final class DebugRoutesCommandTest extends TestCase
{
    public function testCommand()
    {
        $routeCollection = $this->createMock(RouteCollectionInterface::class);
        $routeCollection->method('getRoutes')->willReturn([]);
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects($this->never())->method('clear');
        $debugger = new Debugger($idGenerator, $storage, []);

        $command = new DebugRoutesCommand($routeCollection, $debugger);

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
    }
}
