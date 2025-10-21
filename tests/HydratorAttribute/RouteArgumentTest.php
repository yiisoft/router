<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\HydratorAttribute;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Yiisoft\Hydrator\ArrayData;
use Yiisoft\Hydrator\Attribute\Parameter\ToString;
use Yiisoft\Hydrator\AttributeHandling\Exception\UnexpectedAttributeException;
use Yiisoft\Hydrator\AttributeHandling\ParameterAttributeResolveContext;
use Yiisoft\Hydrator\AttributeHandling\ResolverFactory\ContainerAttributeResolverFactory;
use Yiisoft\Hydrator\Hydrator;
use Yiisoft\Hydrator\Result;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\HydratorAttribute\RouteArgumentResolver;
use Yiisoft\Router\Builder\RouteBuilder as RouterRoute;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class RouteArgumentTest extends TestCase
{
    public function testBase(): void
    {
        $hydrator = $this->createHydrator([
            'a' => 'one',
            'b' => 'two',
            'c' => 'three',
        ]);

        $input = new class () {
            #[RouteArgument('a')]
            public string $a = '';
            #[RouteArgument('b')]
            public string $b = '';
            #[RouteArgument]
            public string $c = '';
        };

        $hydrator->hydrate($input);

        $this->assertSame('one', $input->a);
        $this->assertSame('two', $input->b);
        $this->assertSame('three', $input->c);
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
            public string $c = '';
        };

        $hydrator->hydrate($input);

        $this->assertSame('', $input->a);
        $this->assertSame('', $input->b);
        $this->assertSame('', $input->c);
    }

    public function testUnexpectedAttributeException(): void
    {
        $resolver = new RouteArgumentResolver(new CurrentRoute());

        $attribute = new ToString();
        $context = $this->createParameterAttributeResolveContext();

        $this->expectException(UnexpectedAttributeException::class);
        $this->expectExceptionMessage('Expected "' . RouteArgument::class . '", but "' . ToString::class . '" given.');
        $resolver->getParameterValue($attribute, $context);
    }

    private function createHydrator(array $arguments): Hydrator
    {
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments(RouterRoute::get('/')->toRoute(), $arguments);

        return new Hydrator(
            attributeResolverFactory: new ContainerAttributeResolverFactory(
                new SimpleContainer([
                    RouteArgumentResolver::class => new RouteArgumentResolver($currentRoute),
                ])
            ),
        );
    }

    private function createParameterAttributeResolveContext(): ParameterAttributeResolveContext
    {
        $reflection = new ReflectionFunction(static fn (int $a) => null);

        return new ParameterAttributeResolveContext($reflection->getParameters()[0], Result::fail(), new ArrayData());
    }
}
