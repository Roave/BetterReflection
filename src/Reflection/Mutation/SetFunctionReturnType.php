<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutation;

use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use Roave\BetterReflection\Reflection\Mutator\ReflectionFunctionAbstractMutator;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;

class SetFunctionReturnType
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
     * @param string $returnType
     * @param bool $allowsNull
     * @return ReflectionMethod|ReflectionFunction
     */
    public function __invoke(ReflectionFunctionAbstract $functionAbstractReflection, string $returnType, bool $allowsNull) : ReflectionFunctionAbstract
    {
        $node             = clone $functionAbstractReflection->getAst();
        $nameNode         = new Name($returnType);
        $node->returnType = $allowsNull ? new NullableType($nameNode) : $nameNode;

        return $this->mutator->__invoke($functionAbstractReflection, $node);
    }
}
