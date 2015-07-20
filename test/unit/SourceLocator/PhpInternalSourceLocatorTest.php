<?php

namespace BetterReflectionTest\SourceLocator;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\PhpInternalSourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\AggregateSourceLocator
 */
class PhpInternalSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function testStdClass()
    {
        $reflector = new ClassReflector(new PhpInternalSourceLocator());
        $classInfo = $reflector->reflect('\stdClass');

        $this->assertInstanceOf(ReflectionClass::class, $classInfo);
        $this->assertSame('stdClass', $classInfo->getName());
    }
}
