<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutator;

use Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @internal
 */
final class ReflectionFunctionAbstractMutator
{
    /**
     * @param ReflectionMethod|ReflectionFunction $functionReflection
     * @param ClassMethod|Function_ $node
     * @return ReflectionMethod|ReflectionFunction
     */
    public function __invoke(ReflectionFunctionAbstract $functionAbstractReflection, FunctionLike $node) : ReflectionFunctionAbstract
    {
        $reflector = Closure::bind(function () : Reflector {
            return $this->reflector;
        }, $functionAbstractReflection, ReflectionFunctionAbstract::class)->__invoke();

        if ($functionAbstractReflection instanceof ReflectionMethod) {
            return ReflectionMethod::createFromNode($reflector, $node, $functionAbstractReflection->getDeclaringClass(), $functionAbstractReflection->getImplementingClass());
        }

        $namespaceNode = Closure::bind(function () : ?Namespace_ {
            return $this->declaringNamespace;
        }, $functionAbstractReflection, ReflectionFunctionAbstract::class)->__invoke();

        return ReflectionFunction::createFromNode($reflector, $node, $functionAbstractReflection->getLocatedSource(), $namespaceNode);
    }
}
