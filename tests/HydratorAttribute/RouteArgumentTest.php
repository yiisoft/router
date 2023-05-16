<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\HydratorAttribute;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Yiisoft\Hydrator\Attribute\Parameter\ToString;
use Yiisoft\Hydrator\Context;
use Yiisoft\Hydrator\Hydrator;
use Yiisoft\Hydrator\UnexpectedAttributeException;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\HydratorAttribute\RouteArgumentResolver;
use Yiisoft\Router\Route as RouterRoute;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class RouteArgumentTest extends TestCase
{
    public function testBase(): void
    {
        $hydrator = $this->createHydrator([
            'a' => 'one',
            'b' => 'two',
        ]);

        $input = new class () {
            #[RouteArgument('a')]
            public string $a = '';
            #[RouteArgument('b')]
            public string $b = '';
            #[RouteArgument]
            public array $all = [];
        };

        $hydrator->hydrate($input);

        $this->assertSame('one', $input->a);
        $this->assertSame('two', $input->b);
        $this->assertSame(['a' => 'one', 'b' => 'two'], $input->all);
    }

    public function testWithoutArguments(): void
    {
        $hydrator = $this->createHydrator([]);

        $input = new class () {
            #[RouteArgument('a')]
            public string $a = '';
            #[RouteArgument('b')]
            public string $b = '';
            #[RouteArgument]
            public array $all = [];
        };

        $hydrator->hydrate($input);

        $this->assertSame('', $input->a);
        $this->assertSame('', $input->b);
        $this->assertSame([], $input->all);
    }

    public function testUnexpectedAttributeException(): void
    {
        $resolver = new RouteArgumentResolver(new CurrentRoute());

        $attribute = new ToString();
        $context = $this->createContext();

        $this->expectException(UnexpectedAttributeException::class);
        $this->expectExceptionMessage('Expected "' . RouteArgument::class . '", but "' . ToString::class . '" given.');
        $resolver->getParameterValue($attribute, $context);
    }

    private function createHydrator(array $arguments): Hydrator
    {
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments(RouterRoute::get('/'), $arguments);

        return new Hydrator(
            new SimpleContainer([
                RouteArgumentResolver::class => new RouteArgumentResolver($currentRoute),
            ]),
        );
    }

    private function createContext(): Context
    {
        $reflection = new ReflectionFunction(static fn (int $a) => null);
        return new Context($reflection->getParameters()[0], false, null, [], []);
    }
}
