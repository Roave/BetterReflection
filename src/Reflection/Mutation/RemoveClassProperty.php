<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutation;

use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use Roave\BetterReflection\Reflection\Mutator\ReflectionClassMutator;
use Roave\BetterReflection\Reflection\ReflectionClass;

class RemoveClassProperty
{
    /**
     * @var ReflectionClassMutator
     */
    private $mutator;

    public function __construct()
    {
        $this->mutator = new ReflectionClassMutator();
    }

    public function __invoke(ReflectionClass $classReflection, string $propertyName) : ReflectionClass
    {
        $node = clone $classReflection->getAst();

        $lowerPropertyName = \strtolower($propertyName);
        foreach ($node->stmts as $key => $stmt) {
            if ($stmt instanceof Property) {
                $propertyNames = \array_map(function (PropertyProperty $propertyProperty) : string {
                    return \strtolower($propertyProperty->name);
                }, $stmt->props);

                if (\in_array($lowerPropertyName, $propertyNames, true)) {
                    unset($node->stmts[$key]);
                    break;
                }
            }
        }

        return $this->mutator->__invoke($classReflection, $node);
    }
}
