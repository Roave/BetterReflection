<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutation;

use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use Roave\BetterReflection\Reflection\Mutator\ReflectionParameterMutator;
use Roave\BetterReflection\Reflection\ReflectionParameter;

class SetParameterType
{
    /**
     * @var ReflectionParameterMutator
     */
    private $mutator;

    public function __construct(ReflectionParameterMutator $mutator)
    {
        $this->mutator = $mutator;
    }

    public function __invoke(ReflectionParameter $parameterReflection, string $type, bool $allowsNull) : ReflectionParameter
    {
        $node       = clone $parameterReflection->getAst();
        $nameNode   = new Name($type);
        $node->type = $allowsNull ? new NullableType($nameNode) : $nameNode;

        return $this->mutator->__invoke($parameterReflection, $node);
    }
}
