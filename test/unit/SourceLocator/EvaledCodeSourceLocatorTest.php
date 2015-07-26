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

        eval('class ' . $className . ' {function foo(){}}');

        $locator = new EvaledCodeSourceLocator();

        $source = $locator->__invoke(
            new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        );

        $this->assertInstanceOf(EvaledLocatedSource::class, $source);
        $this->assertStringMatchesFormat('%Aclass%A' . $className . '%A', $source->getSource());
    }

    public function testCanReflectEvaledInterface()
    {
        $interfaceName = uniqid('foo');

        eval('interface ' . $interfaceName . ' {function foo();}');

        $locator = new EvaledCodeSourceLocator();

        $source = $locator->__invoke(
            new Identifier($interfaceName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        );

        $this->assertInstanceOf(EvaledLocatedSource::class, $source);
        $this->assertStringMatchesFormat('%Aclass%A' . $interfaceName . '%A', $source->getSource());
    }

    public function testCanReflectEvaledTrait()
    {
        $traitName = uniqid('foo');

        eval('trait ' . $traitName . ' {function foo(){}}');

        $locator = new EvaledCodeSourceLocator();

        $source = $locator->__invoke(
            new Identifier($traitName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        );

        $this->assertInstanceOf(EvaledLocatedSource::class, $source);
        $this->assertStringMatchesFormat('%Aclass%A' . $traitName . '%A', $source->getSource());
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

    public function testReturnsNullForNonExistentCode()
    {
        $locator = new EvaledCodeSourceLocator();
        $this->assertNull(
            $locator->__invoke(
                new Identifier(
                    'Foo\Bar',
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
                )
            )
        );
    }

    public function testReturnsNullForFunctions()
    {
        $locator = new EvaledCodeSourceLocator();
        $this->assertNull(
            $locator->__invoke(
                new Identifier(
                    'foo',
                    new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
                )
            )
        );
    }
}
