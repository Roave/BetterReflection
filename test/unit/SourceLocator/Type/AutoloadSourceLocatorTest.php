<?php

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Roave\BetterReflection\SourceLocator\Exception\FunctionUndefined;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflectionTest\Fixture\AutoloadableInterface;
use Roave\BetterReflectionTest\Fixture\AutoloadableTrait;
use Roave\BetterReflectionTest\Fixture\ClassForHinting;
use Roave\BetterReflectionTest\Fixture\ExampleClass;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator
 */
class AutoloadSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Reflector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockReflector()
    {
        return $this->createMock(Reflector::class);
    }

    public function testClassLoads()
    {
        $reflector = new ClassReflector(new AutoloadSourceLocator());

        $this->assertFalse(class_exists(ExampleClass::class, false));
        $classInfo = $reflector->reflect(ExampleClass::class);
        $this->assertFalse(class_exists(ExampleClass::class, false));

        $this->assertSame('ExampleClass', $classInfo->getShortName());
    }

    public function testClassLoadsWorksWithExistingClass()
    {
        $reflector = new ClassReflector(new AutoloadSourceLocator());

        // Ensure class is loaded first
        new ClassForHinting();
        $this->assertTrue(class_exists(ClassForHinting::class, false));

        $classInfo = $reflector->reflect(ClassForHinting::class);

        $this->assertSame('ClassForHinting', $classInfo->getShortName());
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanLocateAutoloadableInterface()
    {
        $this->assertFalse(interface_exists(AutoloadableInterface::class, false));

        $this->assertInstanceOf(
            LocatedSource::class,
            (new AutoloadSourceLocator())
                ->locateIdentifier($this->getMockReflector(), new Identifier(
                    AutoloadableInterface::class,
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
                ))->getLocatedSource()
        );

        $this->assertFalse(interface_exists(AutoloadableInterface::class, false));
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanLocateAutoloadedInterface()
    {
        $this->assertTrue(interface_exists(AutoloadableInterface::class));

        $this->assertInstanceOf(
            LocatedSource::class,
            (new AutoloadSourceLocator())
                ->locateIdentifier($this->getMockReflector(), new Identifier(
                    AutoloadableInterface::class,
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
                ))->getLocatedSource()
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanLocateAutoloadableTrait()
    {
        $this->assertFalse(trait_exists(AutoloadableTrait::class, false));

        $this->assertInstanceOf(
            LocatedSource::class,
            (new AutoloadSourceLocator())
                ->locateIdentifier($this->getMockReflector(), new Identifier(
                    AutoloadableTrait::class,
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
                ))->getLocatedSource()
        );

        $this->assertFalse(trait_exists(AutoloadableTrait::class, false));
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanLocateAutoloadedTrait()
    {
        $this->assertTrue(trait_exists(AutoloadableTrait::class));

        $this->assertInstanceOf(
            LocatedSource::class,
            (new AutoloadSourceLocator())
                ->locateIdentifier($this->getMockReflector(), new Identifier(
                    AutoloadableTrait::class,
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
                ))->getLocatedSource()
        );
    }

    public function testFunctionLoads()
    {
        $reflector = new FunctionReflector(new AutoloadSourceLocator());

        require_once(__DIR__ . '/../../Fixture/Functions.php');
        $classInfo = $reflector->reflect('Roave\BetterReflectionTest\Fixture\myFunction');

        $this->assertSame('myFunction', $classInfo->getShortName());
    }

    public function testFunctionReflectionFailsWhenFunctionNotDefined()
    {
        $reflector = new FunctionReflector(new AutoloadSourceLocator());

        $this->expectException(IdentifierNotFound::class);
        $reflector->reflect('this function does not exist, hopefully');
    }

    public function testNullReturnedWhenInvalidTypeGiven()
    {
        $locator = new AutoloadSourceLocator();

        $type = new IdentifierType();
        $typeReflection = new \ReflectionObject($type);
        $prop = $typeReflection->getProperty('name');
        $prop->setAccessible(true);
        $prop->setValue($type, 'nonsense');

        $identifier = new Identifier('foo', $type);
        $this->assertNull($locator->locateIdentifier($this->getMockReflector(), $identifier));
    }

    public function testReturnsNullWhenUnableToAutoload()
    {
        $sourceLocator = new AutoloadSourceLocator();

        $this->assertNull($sourceLocator->locateIdentifier(
            new ClassReflector($sourceLocator),
            new Identifier('Some\Class\That\Cannot\Exist', new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        ));
    }

    public function testShouldNotConsiderEvaledSources()
    {
        $className = uniqid('generatedClassName');

        eval('class ' . $className . '{}');

        $this->assertNull(
            (new AutoloadSourceLocator())
                ->locateIdentifier($this->getMockReflector(), new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)))
        );
    }
}
