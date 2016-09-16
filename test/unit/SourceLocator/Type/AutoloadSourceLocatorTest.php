<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\Reflector\FunctionReflector;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use BetterReflection\SourceLocator\Exception\FunctionUndefined;
use BetterReflection\SourceLocator\Located\LocatedSource;
use BetterReflectionTest\Fixture\AutoloadableInterface;
use BetterReflectionTest\Fixture\AutoloadableTrait;
use BetterReflectionTest\Fixture\ClassForHinting;

/**
 * @covers \BetterReflection\SourceLocator\Type\AutoloadSourceLocator
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

        $className = 'BetterReflectionTest\Fixture\ExampleClass';
        $this->assertFalse(class_exists($className, false));
        $classInfo = $reflector->reflect($className);
        $this->assertFalse(class_exists($className, false));

        $this->assertSame('ExampleClass', $classInfo->getShortName());
    }

    public function testClassLoadsWorksWithExistingClass()
    {
        $reflector = new ClassReflector(new AutoloadSourceLocator());

        // Ensure class is loaded first
        new ClassForHinting();
        $className = 'BetterReflectionTest\Fixture\ClassForHinting';
        $this->assertTrue(class_exists($className, false));

        $classInfo = $reflector->reflect($className);

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
        $classInfo = $reflector->reflect('BetterReflectionTest\Fixture\myFunction');

        $this->assertSame('myFunction', $classInfo->getShortName());
    }

    public function testFunctionReflectionFailsWhenFunctionNotDefined()
    {
        $reflector = new FunctionReflector(new AutoloadSourceLocator());

        $this->expectException(FunctionUndefined::class);
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
