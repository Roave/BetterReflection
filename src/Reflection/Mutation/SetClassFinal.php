<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutation;

use PhpParser\Node\Stmt\Class_;
use Roave\BetterReflection\Reflection\Exception\NotAClassReflection;
use Roave\BetterReflection\Reflection\Mutator\ReflectionClassMutator;
use Roave\BetterReflection\Reflection\ReflectionClass;

class SetClassFinal
{
    /**
     * @var ReflectionClassMutator
     */
    private $mutator;

    public function __construct()
    {
        $this->mutator = new ReflectionClassMutator();
    }

    /**
     * @param ReflectionClass $classReflection
     * @param bool $final
     * @return ReflectionClass
     * @throws NotAClassReflection
     */
    public function __invoke(ReflectionClass $classReflection, bool $final) : ReflectionClass
    {
        $node = clone $classReflection->getAst();

        if ( ! $node instanceof Class_) {
            throw NotAClassReflection::fromReflectionClass($classReflection);
        }

        if ($final) {
            $node->flags |= Class_::MODIFIER_FINAL;
        } else {
            $node->flags &= ~Class_::MODIFIER_FINAL;
        }

        return $this->mutator->__invoke($classReflection, $node);
    }
}
