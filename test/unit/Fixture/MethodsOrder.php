<?php

namespace Roave\BetterReflectionTest\Fixture;

interface MethodsOrderParentInterface
{
    public function f10();
}

interface MethodsOrderInterface extends MethodsOrderParentInterface
{
    public function f9();
}

interface InterfaceForMethodsOrderParent
{
    public function f6();
}

trait TraitForMethodsOrderParent
{
    public function f5()
    {
        // Not used
    }
}

abstract class MethodsOrderParent implements InterfaceForMethodsOrderParent
{
    use TraitForMethodsOrderParent;

    public function f3()
    {
    }

    public function f1()
    {
        // Not used
    }

    public function f4()
    {
    }
}

trait TraitForMethodsOrderTrait
{
    public function f8()
    {
    }
}

trait MethodsOrderTrait
{
    use TraitForMethodsOrderTrait;

    public function f2()
    {
        // Not used
    }

    public function f7()
    {
    }

    abstract function f4(); // Not used
}

abstract class MethodsOrder extends MethodsOrderParent implements MethodsOrderInterface
{
    use MethodsOrderTrait;

    public function f1()
    {
    }

    public function f2()
    {
    }
}
