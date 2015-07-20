<?php

namespace
{
    // note: this will crash if loaded in PHP, as the Traversable interface is already in core
    interface Traversable {}
}

namespace BetterReflectionTest\ClassesImplementingIterators
{
    class TraversableImplementation implements \Traversable
    {
    }

    class NonTraversableImplementation
    {
    }

    abstract class AbstractTraversableImplementation implements \Traversable
    {
    }

    interface TraversableExtension extends \Traversable
    {
    }
}
