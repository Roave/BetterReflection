<?php

namespace Roave\BetterReflectionTest\Fixture;

interface StringCastClassInterface
{
    public function prototypeMethod();
}

interface StringCastClassInterface2
{
}

abstract class StringCastClassParent
{
    public function overwrittenMethod()
    {
    }
}

abstract class StringCastClass extends StringCastClassParent implements StringCastClassInterface, StringCastClassInterface2
{
    public const PUBLIC_CONSTANT = true;
    protected const PROTECTED_CONSTANT = 0;
    private const PRIVATE_CONSTANT = 'string';
    const NO_VISIBILITY_CONSTANT = [];

    private $privateProperty = 'string';
    protected $protectedProperty = 0;
    public $publicProperty = true;
    public static $publicStaticProperty = null;

    public function __construct()
    {
    }

    public function __destruct()
    {
    }

    public function publicMethod()
    {
    }

    protected function protectedMethod()
    {
    }

    private function privateMethod()
    {
    }

    final public function finalPublicMethod()
    {
    }

    abstract public function abstractPublicMethod();

    public static function staticPublicMethod()
    {
    }

    function noVisibility()
    {
    }

    public function overwrittenMethod()
    {
    }

    public function prototypeMethod()
    {
    }

    public function methodWithParameters($a, $b)
    {
    }
}
