<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutation;

use InvalidArgumentException;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use ReflectionMethod as CoreReflectionMethod;
use Roave\BetterReflection\Reflection\Mutator\ReflectionClassMutator;
use Roave\BetterReflection\Reflection\ReflectionClass;

class AddClassMethod
{
    /**
     * @var ReflectionClassMutator
     */
    private $mutator;

    public function __construct()
    {
        $this->mutator = new ReflectionClassMutator();
    }

    public function __invoke(ReflectionClass $classReflection, string $methodName, int $modifiers) : ReflectionClass
    {
        $modifiersMapping = [
            CoreReflectionMethod::IS_STATIC    => Class_::MODIFIER_STATIC,
            CoreReflectionMethod::IS_PUBLIC    => Class_::MODIFIER_PUBLIC,
            CoreReflectionMethod::IS_PROTECTED => Class_::MODIFIER_PROTECTED,
            CoreReflectionMethod::IS_PRIVATE   => Class_::MODIFIER_PRIVATE,
            CoreReflectionMethod::IS_ABSTRACT  => Class_::MODIFIER_ABSTRACT,
            CoreReflectionMethod::IS_FINAL     => Class_::MODIFIER_FINAL,
        ];

        $flags = 0;
        foreach ($modifiersMapping as $modifier => $flag) {
            if ($modifiers & $modifier) {
                $flags     |= $flag;
                $modifiers &= ~$modifier;
            }
        }

        if ($modifiers > 0) {
            throw new InvalidArgumentException('Modifiers should be combination of \ReflectionMethod::IS_* constants');
        }

        $node          = clone $classReflection->getAst();
        $node->stmts[] = new ClassMethod($methodName, ['flags' => $flags]);

        return $this->mutator->__invoke($classReflection, $node);
    }
}
