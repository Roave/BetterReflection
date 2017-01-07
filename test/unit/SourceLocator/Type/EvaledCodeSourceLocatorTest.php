<?php

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Roave\BetterReflection\SourceLocator\Located\EvaledLocatedSource;
use Roave\BetterReflection\SourceLocator\Ast\Locator;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator
 */
class EvaledCodeSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var ReflectionClass
     */
    private $reflectionClass;

    /**
     * @var Locator
     */
    private $locator;

    public function setUp()
    {
        $this->reflector = $this->createMock(Reflector::class);
        $this->reflectionClass = $this->createMock(ReflectionClass::class);
    }

    private function createLocator($expects = false)
    {
        $locator = $this->createMock(Locator::class);

        $locator
            ->expects($expects ? $this->once() : $this->never())
            ->method('findReflection')
            ->will($this->returnCallback(
                function ($reflector, $locatedSource, $identifier) { 
                    $this->reflectionClass
                        ->method('getLocatedSource')
                        ->will($this->returnvalue($locatedSource));
                    $this->reflectionClass
                        ->method('getName')
                        ->will($this->returnValue($identifier->getName()));

                    return $this->reflectionClass;
                }
            ));

        return $locator;
    }

    public function testCanReflectEvaledClass()
    {
        $className = uniqid('foo');

        eval('class ' . $className . ' {function foo(){}}');

        $locator = new EvaledCodeSourceLocator($this->createLocator(true));

        /** @var ReflectionClass $reflection */
        $reflection = $locator->locateIdentifier(
            $this->reflector,
            new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        );
        $source = $reflection->getLocatedSource();

        $this->assertInstanceOf(EvaledLocatedSource::class, $source);
        $this->assertStringMatchesFormat('%Aclass%A' . $className . '%A', $source->getSource());
    }

    public function testCanReflectEvaledInterface()
    {
        $interfaceName = uniqid('foo');

        eval('interface ' . $interfaceName . ' {function foo();}');

        $locator = new EvaledCodeSourceLocator($this->createLocator(true));

        $reflection = $locator->locateIdentifier(
            $this->reflector,
            new Identifier($interfaceName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        );

        $this->assertInstanceOf(EvaledLocatedSource::class, $reflection->getLocatedSource());
        $this->assertStringMatchesFormat('%Ainterface%A' . $interfaceName . '%A', $reflection->getLocatedSource()->getSource());
    }

    public function testCanReflectEvaledTrait()
    {
        $traitName = uniqid('foo');

        eval('trait ' . $traitName . ' {function foo(){}}');

        $locator = new EvaledCodeSourceLocator($this->createLocator(true));

        $reflection = $locator->locateIdentifier(
            $this->reflector,
            new Identifier($traitName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        );

        $this->assertInstanceOf(EvaledLocatedSource::class, $reflection->getLocatedSource());
        $this->assertStringMatchesFormat('%Atrait%A' . $traitName . '%A', $reflection->getLocatedSource()->getSource());
    }

    public function testCanReflectEvaledLocatedSourceClass()
    {
        /* @var $class */
        $reflector = (new ClassReflector(new EvaledCodeSourceLocator($this->createLocator(true))));
        $className = uniqid('foo');

        eval('class ' . $className . ' {function foo($bar = "baz") {}}');

        $class = $reflector->reflect($className);

        $this->assertInstanceOf(ReflectionClass::class, $class);
        $this->assertSame($className, $class->getName());
    }

    public function testCannotReflectRequiredClass()
    {
        $this->assertNull(
            (new EvaledCodeSourceLocator($this->createLocator()))
                ->locateIdentifier($this->reflector, new Identifier(__CLASS__, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)))
        );
    }

    public function testReturnsNullForNonExistentCode()
    {
        $locator = new EvaledCodeSourceLocator($this->createLocator());
        $this->assertNull(
            $locator->locateIdentifier(
                $this->reflector,
                new Identifier(
                    'Foo\Bar',
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
                )
            )
        );
    }

    public function testReturnsNullForFunctions()
    {
        $locator = new EvaledCodeSourceLocator($this->createLocator());
        $this->assertNull(
            $locator->locateIdentifier(
                $this->reflector,
                new Identifier(
                    'foo',
                    new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
                )
            )
        );
    }
}
