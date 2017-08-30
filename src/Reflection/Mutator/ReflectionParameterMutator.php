<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutator;

use Closure;
use PhpParser\Node\Param;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @internal
 */
final class ReflectionParameterMutator
{
    public function __invoke(ReflectionParameter $propertyReflection, Param $node) : ReflectionParameter
    {
        $reflector = Closure::bind(function () : Reflector {
            return $this->reflector;
        }, $propertyReflection, ReflectionParameter::class)->__invoke();

        return ReflectionParameter::createFromNode($reflector, $node, $propertyReflection->getDeclaringFunction(), $propertyReflection->getPosition());
    }
}
