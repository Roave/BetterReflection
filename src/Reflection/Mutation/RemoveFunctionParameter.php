<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutation;

use Roave\BetterReflection\Reflection\Mutator\ReflectionFunctionAbstractMutator;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;

class RemoveFunctionParameter
{
    /**
     * @var ReflectionFunctionAbstractMutator
     */
    private $mutator;

    public function __construct()
    {
        $this->mutator = new ReflectionFunctionAbstractMutator();
    }

    /**
     * @param ReflectionMethod|ReflectionFunction $functionReflection
     * @param string $parameterName
     * @return ReflectionMethod|ReflectionFunction
     */
    public function __invoke(ReflectionFunctionAbstract $functionAbstractReflection, string $parameterName) : ReflectionFunctionAbstract
    {
        $node = clone $functionAbstractReflection->getAst();

        $lowerParameterName = \strtolower($parameterName);
        foreach ($node->params as $key => $parameterNode) {
            if (\strtolower($parameterNode->name) === $lowerParameterName) {
                unset($node->params[$key]);
            }
        }
        $node->params = \array_values($node->params);

        return $this->mutator->__invoke($functionAbstractReflection, $node);
    }
}
