<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutator;

use Closure;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Namespace_;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @internal
 */
final class ReflectionClassMutator
{
    public function __invoke(ReflectionClass $classReflection, ClassLike $node) : ReflectionClass
    {
        $reflector = Closure::bind(function () : Reflector {
            return $this->reflector;
        }, $classReflection, ReflectionClass::class)->__invoke();

        $namespaceNode = Closure::bind(function () : ?Namespace_ {
            return $this->declaringNamespace;
        }, $classReflection, ReflectionClass::class)->__invoke();

        return ReflectionClass::createFromNode($reflector, $node, $classReflection->getLocatedSource(), $namespaceNode);
    }
}
