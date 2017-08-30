<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutation;

use InvalidArgumentException;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Mutator\ReflectionClassMutator;
use Roave\BetterReflection\Reflection\ReflectionClass;

class AddClassProperty
{
    /**
     * @var ReflectionClassMutator
     */
    private $mutator;

    public function __construct()
    {
        $this->mutator = new ReflectionClassMutator();
    }

    public function __invoke(ReflectionClass $classReflection, string $propertyName, int $visibility, bool $static) : ReflectionClass
    {
        $type = 0;
        switch ($visibility) {
            case CoreReflectionProperty::IS_PRIVATE:
                $type |= Class_::MODIFIER_PRIVATE;
                break;
            case CoreReflectionProperty::IS_PROTECTED:
                $type |= Class_::MODIFIER_PROTECTED;
                break;
            case CoreReflectionProperty::IS_PUBLIC:
                $type |= Class_::MODIFIER_PUBLIC;
                break;
            default:
                throw new InvalidArgumentException('Visibility should be one of the \ReflectionProperty::IS_* constant');
        }

        if ($static) {
            $type |= Class_::MODIFIER_STATIC;
        }

        $node          = clone $classReflection->getAst();
        $node->stmts[] = new Property($type, [new PropertyProperty($propertyName)]);

        return $this->mutator->__invoke($classReflection, $node);
    }
}
