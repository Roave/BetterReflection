<?php

namespace Roave\BetterReflectionTest\Fixture;

abstract class AbstractClassImplementingMethodFromTrait
{
    public function foo(): void
    {
    }

    public function bar(): void
    {
    }
}

trait TraitWithAbstractMethod
{
    abstract public function foo(): void;

    public function bar(): void
    {
    }
}

trait TraitWithBoo
{
    public function boo(): void
    {
    }
}

class ClassUsingTraitWithAbstractMethod extends AbstractClassImplementingMethodFromTrait
{
    use TraitWithAbstractMethod;

    public function boo(): void
    {
    }
}

class ClassExtendingNonAbstractClass extends ClassUsingTraitWithAbstractMethod
{
    use TraitWithBoo;
}

trait AbstractTrait
{
    abstract public function bar(): void;
}

trait ImplementationTrait
{
    public function bar(): void
    {
    }
}

class ClassUsesTwoTraitsWithSameMethodNameOneIsAbstract
{
    use AbstractTrait;
    use ImplementationTrait;
}
