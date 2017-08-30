<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutator;

use Closure;
use PhpParser\Node\Stmt\Property;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @internal
 */
final class ReflectionPropertyMutator
{
    public function __invoke(ReflectionProperty $propertyReflection, Property $node) : ReflectionProperty
    {
        $reflector = Closure::bind(function () : Reflector {
            return $this->reflector;
        }, $propertyReflection, ReflectionProperty::class)->__invoke();

        return ReflectionProperty::createFromNode($reflector, $node, $propertyReflection->getDeclaringClass(), $propertyReflection->getImplementingClass());
    }
}
