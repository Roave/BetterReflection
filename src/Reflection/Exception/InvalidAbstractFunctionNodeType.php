<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Exception;

use InvalidArgumentException;
use PhpParser\Node;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;

use function get_class;
use function sprintf;

class InvalidAbstractFunctionNodeType extends InvalidArgumentException
{
    public static function fromNode(Node $node): self
    {
        return new self(sprintf(
            'Node for "%s" must be "%s" or "%s", was a "%s"',
            ReflectionFunctionAbstract::class,
            Node\Stmt\ClassMethod::class,
            Node\FunctionLike::class,
            get_class($node),
        ));
    }
}
