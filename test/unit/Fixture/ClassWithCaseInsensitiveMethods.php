<?php

namespace Roave\BetterReflectionTest\Fixture;

class ParentForClassWithCaseInsensitiveMethods
{
    public function FOO()
    {
    }
}

class ClassWithCaseInsensitiveMethods extends ParentForClassWithCaseInsensitiveMethods
{
    public function foo()
    {
    }
}
