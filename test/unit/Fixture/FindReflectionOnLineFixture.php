<?php

function fooFunc()
{
    // hello
}

class SomeFooClass
{
    private $someMember;

    public function someMethod()
    {
        // hello
    }
}

trait SomeFooTrait
{
}

interface SomeFooInterface
{
}

class SomeFooClassWithImplementedInterface implements \Roave\BetterReflectionTest\Fixture\AutoloadableInterface
{
}

define('FOO', 0);

const BOO = 1;

class SomeFooClassWithImplementedInternalInterface implements \IteratorAggregate
{
    public function getIterator(): Traversable
    {
    }
}

class SomeFooClassWithImplementedInterfaceFromSameFile implements SomeFooInterface
{
}
