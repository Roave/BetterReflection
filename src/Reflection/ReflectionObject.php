<?php

namespace BetterReflection\Reflection;

use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\EvaledCodeSourceLocator;

class ReflectionObject extends ReflectionClass
{
    private function __construct()
    {
    }

    /**
     * Pass an instance of an object to this method to reflect it
     *
     * @param object $object
     * @return ReflectionClass
     */
    public static function createFromInstance($object)
    {
        if (gettype($object) !== 'object') {
            throw new \InvalidArgumentException('Can only create from an instance of an object');
        }

        return (new ClassReflector(new EvaledCodeSourceLocator()))
            ->reflect(get_class($object));
    }
}
