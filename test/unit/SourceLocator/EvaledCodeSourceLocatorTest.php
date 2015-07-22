<?php

namespace BetterReflectionTest\SourceLocator;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\EvaledCodeSourceLocator;
use BetterReflection\SourceLocator\EvaledLocatedSource;

/**
 * @covers \BetterReflection\SourceLocator\EvaledCodeSourceLocator
 */
class EvaledCodeSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function testCanReflectEvaledClass()
    {
        $className = uniqid('foo');

        eval('class ' . $className . ' {function foo() {}}');

        $locator = new EvaledCodeSourceLocator();

        $source = $locator->__invoke(
            new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        );

        $this->assertInstanceOf(EvaledLocatedSource::class, $source);
        $this->assertNotEmpty($source->getSource());
    }

    public function testCanReflectEvaledLocatedSourceClass()
    {
        /* @var $class */
        $reflector = (new ClassReflector(new EvaledCodeSourceLocator()));
        $className = uniqid('foo');

        eval('class ' . $className . ' {function foo($bar = "baz") {}}');

        $class = $reflector->reflect($className);

        $this->assertInstanceOf(ReflectionClass::class, $class);
        $this->assertSame($className, $class->getName());
        $this->assertFalse($class->isInternal());
        $this->assertTrue($class->isUserDefined());
        $this->assertNull($class->getFileName());
        $this->assertCount(1, $class->getMethods());
    }
}
