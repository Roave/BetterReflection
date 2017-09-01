<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutator;

final class ReflectionMutators
{
    /**
     * @var ReflectionClassMutator|null
     */
    private $classMutator;

    /**
     * @var ReflectionFunctionAbstractMutator|null
     */
    private $functionMutator;

    /**
     * @var ReflectionParameterMutator|null
     */
    private $parameterMutator;

    /**
     * @var ReflectionPropertyMutator|null
     */
    private $propertyMutator;

    public function classMutator() : ReflectionClassMutator
    {
        return $this->classMutator
            ?? $this->classMutator = new ReflectionClassMutator();
    }

    public function functionMutator() : ReflectionFunctionAbstractMutator
    {
        return $this->functionMutator
            ?? $this->functionMutator = new ReflectionFunctionAbstractMutator();
    }

    public function parameterMutator() : ReflectionParameterMutator
    {
        return $this->parameterMutator
            ?? $this->parameterMutator = new ReflectionParameterMutator();
    }

    public function propertyMutator() : ReflectionPropertyMutator
    {
        return $this->propertyMutator
            ?? $this->propertyMutator = new ReflectionPropertyMutator();
    }
}
