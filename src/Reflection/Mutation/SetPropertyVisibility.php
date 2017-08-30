<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutation;

use InvalidArgumentException;
use PhpParser\Node\Stmt\Class_;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Mutator\ReflectionPropertyMutator;
use Roave\BetterReflection\Reflection\ReflectionProperty;

class SetPropertyVisibility
{
    /**
     * @var ReflectionPropertyMutator
     */
    private $mutator;

    public function __construct()
    {
        $this->mutator = new ReflectionPropertyMutator();
    }

    /**
     * @param ReflectionProperty $propertyReflection
     * @param int $visibility
     * @return ReflectionProperty
     * @throws InvalidArgumentException
     */
    public function __invoke(ReflectionProperty $propertyReflection, int $visibility) : ReflectionProperty
    {
        $node = clone $propertyReflection->getAst();

        $node->flags &= ~Class_::MODIFIER_PRIVATE & ~Class_::MODIFIER_PROTECTED & ~Class_::MODIFIER_PUBLIC;

        switch ($visibility) {
            case CoreReflectionProperty::IS_PRIVATE:
                $node->flags |= Class_::MODIFIER_PRIVATE;
                break;
            case CoreReflectionProperty::IS_PROTECTED:
                $node->flags |= Class_::MODIFIER_PROTECTED;
                break;
            case CoreReflectionProperty::IS_PUBLIC:
                $node->flags |= Class_::MODIFIER_PUBLIC;
                break;
            default:
                throw new InvalidArgumentException('Visibility should be \ReflectionProperty::IS_PRIVATE, ::IS_PROTECTED or ::IS_PUBLIC constants');
        }

        return $this->mutator->__invoke($propertyReflection, $node);
    }
}
