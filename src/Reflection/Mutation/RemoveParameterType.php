<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutation;

use Roave\BetterReflection\Reflection\Mutator\ReflectionParameterMutator;
use Roave\BetterReflection\Reflection\ReflectionParameter;

class RemoveParameterType
{
    /**
     * @var ReflectionParameterMutator
     */
    private $mutator;

    public function __construct()
    {
        $this->mutator = new ReflectionParameterMutator();
    }

    public function __invoke(ReflectionParameter $parameterReflection) : ReflectionParameter
    {
        $node       = clone $parameterReflection->getAst();
        $node->type = null;

        return $this->mutator->__invoke($parameterReflection, $node);
    }
}
