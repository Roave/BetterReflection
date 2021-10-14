<?php

namespace Roave\BetterReflectionTest\Fixture;

class StringCastProperties
{
    private $privateProperty = 'string';
    protected $protectedProperty = 0;
    public $publicProperty = true;
    public static $publicStaticProperty = null;

    public int $namedTypeProperty = 0;
    public int|bool $unionTypeProperty = false;
    public ?int $nullableTypeProperty = null;

    public readonly int $readOnlyProperty;
}
