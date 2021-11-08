<?php

namespace Roave\BetterReflectionTest\Fixture;

class StaticPropertiesParent
{
    public static $parentBaz = 'parentBaz';
    protected static $parentBat = 456;
}

class StaticProperties extends StaticPropertiesParent
{
    public static $baz = 'baz';
    protected static $bat = 123;
    private static $qux;

    private $notStaticProperty;
}
