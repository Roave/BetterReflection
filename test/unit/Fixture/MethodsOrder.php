<?php

namespace Rector\BetterReflectionTest\Fixture;

interface MethodsOrderInterface
{
    public function forth();
}

class MethodsOrderParent
{
    public function third()
    {
    }

    public function first()
    {
    }
}

trait MethodsOrderTrait
{
    public function second()
    {
    }
}

abstract class MethodsOrder extends MethodsOrderParent implements MethodsOrderInterface
{
    use MethodsOrderTrait;

    public function first()
    {
    }
}
