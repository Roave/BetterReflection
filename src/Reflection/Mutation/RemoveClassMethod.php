<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutation;

use PhpParser\Node\Stmt\ClassMethod;
use Roave\BetterReflection\Reflection\Mutator\ReflectionClassMutator;
use Roave\BetterReflection\Reflection\ReflectionClass;

class RemoveClassMethod
{
    /**
     * @var ReflectionClassMutator
     */
    private $mutator;

    public function __construct(ReflectionClassMutator $mutator)
    {
        $this->mutator = $mutator;
    }

    public function __invoke(ReflectionClass $classReflection, string $methodName) : ReflectionClass
    {
        $node = clone $classReflection->getAst();

        $lowerMethodName = \strtolower($methodName);
        foreach ($node->stmts as $key => $stmt) {
            if ($stmt instanceof ClassMethod && $lowerMethodName === \strtolower($stmt->name)) {
                unset($node->stmts[$key]);
                break;
            }
        }

        return $this->mutator->__invoke($classReflection, $node);
    }
}
