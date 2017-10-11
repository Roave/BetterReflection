<?php

namespace Rector\BetterReflectionTest\Fixture;

class StringCastProperties
{
    private $privateProperty = 'string';
    protected $protectedProperty = 0;
    public $publicProperty = true;
    public static $publicStaticProperty = null;
}
