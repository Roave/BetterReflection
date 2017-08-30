<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutation;

use PhpParser\Node;
use Roave\BetterReflection\Reflection\Mutator\ReflectionFunctionAbstractMutator;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;

class SetFunctionBodyFromAst
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
     * @param Node[] $nodes
     * @return ReflectionMethod|ReflectionFunction
     */
    public function __invoke(ReflectionFunctionAbstract $functionAbstractReflection, array $nodes) : ReflectionFunctionAbstract
    {
        // This slightly confusing code simply type-checks the $nodes
        // array by unpacking them and splatting them in the closure.
        $validator = function (Node ...$node) : array {
            return $node;
        };

        $node        = clone $functionAbstractReflection->getAst();
        $node->stmts = $validator(...$nodes);

        return $this->mutator->__invoke($functionAbstractReflection, $node);
    }
}
