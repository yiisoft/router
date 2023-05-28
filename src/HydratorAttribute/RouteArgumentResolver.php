<?php

declare(strict_types=1);

namespace Yiisoft\Router\HydratorAttribute;

use Yiisoft\Hydrator\Context;
use Yiisoft\Hydrator\ParameterAttributeInterface;
use Yiisoft\Hydrator\ParameterAttributeResolverInterface;
use Yiisoft\Hydrator\Result;
use Yiisoft\Hydrator\UnexpectedAttributeException;
use Yiisoft\Router\CurrentRoute;

final class RouteArgumentResolver implements ParameterAttributeResolverInterface
{
    public function __construct(
        private CurrentRoute $currentRoute,
    ) {
    }

    public function getParameterValue(ParameterAttributeInterface $attribute, Context $context): Result
    {
        if (!$attribute instanceof RouteArgument) {
            throw new UnexpectedAttributeException(RouteArgument::class, $attribute);
        }

        $arguments = $this->currentRoute->getArguments();

        $name = $attribute->getName() ?? $context->getParameter()->getName();

        if (array_key_exists($name, $arguments)) {
            return Result::success($arguments[$name]);
        }

        return Result::fail();
    }
}
