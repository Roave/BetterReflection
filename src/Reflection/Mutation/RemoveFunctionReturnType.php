<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutation;

use Roave\BetterReflection\Reflection\Mutator\ReflectionFunctionAbstractMutator;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;

class RemoveFunctionReturnType
{
    /**
     * @var ReflectionFunctionAbstractMutator
     */
    private $mutator;

    public function __construct(ReflectionFunctionAbstractMutator $mutator)
    {
        $this->mutator = $mutator;
    }

    /**
     * @param ReflectionMethod|ReflectionFunction $functionReflection
     * @return ReflectionMethod|ReflectionFunction
     */
    public function __invoke(ReflectionFunctionAbstract $functionAbstractReflection) : ReflectionFunctionAbstract
    {
        $node             = clone $functionAbstractReflection->getAst();
        $node->returnType = null;

        return $this->mutator->__invoke($functionAbstractReflection, $node);
    }
}
