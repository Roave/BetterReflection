<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionMethod;
use BetterReflection\Reflection\ReflectionParameter;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Located\InternalLocatedSource;
use BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use ReflectionClass as PhpReflectionClass;

/**
 * @covers \BetterReflection\SourceLocator\Type\PhpInternalSourceLocator
 */
class PhpInternalSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Reflector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockReflector()
    {
        return $this->getMock(Reflector::class);
    }

    /**
     * @dataProvider internalSymbolsProvider
     *
     * @param string $className
     */
    public function testCanFetchInternalLocatedSource($className)
    {
        $locator = new PhpInternalSourceLocator();

        try {
            /** @var ReflectionClass $reflection */
            $reflection = $locator->locateIdentifier(
                $this->getMockReflector(),
                new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
            );
            $source = $reflection->getLocatedSource();

            $this->assertInstanceOf(InternalLocatedSource::class, $source);
            $this->assertNotEmpty($source->getSource());
        } catch (\ReflectionException $e) {
            $this->markTestIncomplete(sprintf(
                'Can\'t reflect class "%s" due to an internal reflection exception: "%s". Consider adding a stub class',
                $className,
                $e->getMessage()
            ));
        }
    }

    /**
     * @dataProvider internalSymbolsProvider
     *
     * @param string $className
     * @throws \ReflectionException
     */
    public function testCanReflectInternalClasses($className)
    {
        /* @var $class */
        $phpInternalSourceLocator = new PhpInternalSourceLocator();
        $reflector = (new ClassReflector($phpInternalSourceLocator));

        try {
            $class = $reflector->reflect($className);
        } catch (\ReflectionException $e) {
            if ($phpInternalSourceLocator->hasStub($className)) {
                throw $e;
            }

            $this->markTestIncomplete(sprintf(
                'Can\'t reflect class "%s" due to an internal reflection exception: "%s". Consider adding a stub class',
                $className,
                $e->getMessage()
            ));
        }

        $this->assertInstanceOf(ReflectionClass::class, $class);
        $this->assertSame($className, $class->getName());
        $this->assertTrue($class->isInternal());
        $this->assertFalse($class->isUserDefined());

        $internalReflection = new \ReflectionClass($className);

        $this->assertSame($internalReflection->isInterface(), $class->isInterface());
        $this->assertSame($internalReflection->isTrait(), $class->isTrait());
    }

    /**
     * @return string[] internal symbols
     */
    public function internalSymbolsProvider()
    {
        $allSymbols = array_merge(
            get_declared_classes(),
            get_declared_interfaces(),
            get_declared_traits()
        );

        $indexedSymbols = array_combine($allSymbols, $allSymbols);

        return array_map(
            function ($symbol) {
                return [$symbol];
            },
            array_filter(
                $indexedSymbols,
                function ($symbol) {
                    $reflection = new PhpReflectionClass($symbol);

                    return $reflection->isInternal();
                }
            )
        );
    }

    public function testReturnsNullForNonExistentCode()
    {
        $locator = new PhpInternalSourceLocator();
        $this->assertNull(
            $locator->locateIdentifier(
                $this->getMockReflector(),
                new Identifier(
                    'Foo\Bar',
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
                )
            )
        );
    }

    public function testReturnsNullForFunctions()
    {
        $locator = new PhpInternalSourceLocator();
        $this->assertNull(
            $locator->locateIdentifier(
                $this->getMockReflector(),
                new Identifier(
                    'foo',
                    new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
                )
            )
        );
    }

    /**
     * @dataProvider stubbedClassesProvider
     *
     * @param string $className
     */
    public function testAllGeneratedStubsAreInSyncWithInternalReflectionClasses($className)
    {
        if (! (
            class_exists($className, false)
            || interface_exists($className, false)
            || trait_exists($className, false)
        )) {
            $this->markTestSkipped(sprintf('Class "%s" is not available in this environment', $className));
        }

        $reflector = new ClassReflector(new PhpInternalSourceLocator());

        $this->assertSameClassAttributes(new \ReflectionClass($className), $reflector->reflect($className));
    }

    /**
     * @return string[][]
     */
    public function stubbedClassesProvider()
    {
        $classNames = array_filter(
            str_replace('.stub', '', scandir(__DIR__ . '/../../../../stub')),
            function ($fileName) {
                return trim($fileName, '.');
            }
        );

        return array_combine(
            $classNames,
            array_map(
                function ($fileName) {
                    return [$fileName];
                },
                $classNames
            )
        );
    }

    private function assertSameClassAttributes(\ReflectionClass $original, ReflectionClass $stubbed)
    {
        $this->assertSame($original->getName(), $stubbed->getName());

        $internalParent     = $original->getParentClass();
        $betterParent       = $stubbed->getParentClass();
        $internalParentName = $internalParent ? $internalParent->getName() : null;
        $betterParentName   = $betterParent ? $betterParent->getName() : null;

        $this->assertSame($internalParentName, $betterParentName);

        $originalMethods = $original->getMethods();

        $originalMethodNames = array_map(
            function (\ReflectionMethod $method) {
                return $method->getName();
            },
            $originalMethods
        );

        $stubbedMethodNames = array_map(
            function (ReflectionMethod $method) {
                return $method->getName();
            },
            $stubbed->getMethods() // @TODO see #107
        );

        sort($originalMethodNames);
        sort($stubbedMethodNames);

        $this->assertSame($originalMethodNames, $stubbedMethodNames);
        $this->assertEquals($original->getConstants(), $stubbed->getConstants());

        foreach ($originalMethods as $method) {
            $this->assertSameMethodAttributes($method, $stubbed->getMethod($method->getName()));
        }
    }

    private function assertSameMethodAttributes(\ReflectionMethod $original, ReflectionMethod $stubbed)
    {
        $this->assertSame(
            array_map(
                function (\ReflectionParameter $parameter) {
                    return $parameter->getDeclaringFunction()->getName() . '.' . $parameter->getName();
                },
                $original->getParameters()
            ),
            array_map(
                function (ReflectionParameter $parameter) {
                    return $parameter->getDeclaringFunction()->getName() . '.' . $parameter->getName();
                },
                $stubbed->getParameters()
            )
        );

        foreach ($original->getParameters() as $parameter) {
            $this->assertSameParameterAttributes($parameter, $stubbed->getParameter($parameter->getName()));
        }

        $this->assertSame($original->isPublic(), $stubbed->isPublic());
        $this->assertSame($original->isPrivate(), $stubbed->isPrivate());
        $this->assertSame($original->isProtected(), $stubbed->isProtected());
        $this->assertSame($original->returnsReference(), $stubbed->returnsReference());
        $this->assertSame($original->isStatic(), $stubbed->isStatic());
        $this->assertSame($original->isFinal(), $stubbed->isFinal());
    }

    private function assertSameParameterAttributes(\ReflectionParameter $original, ReflectionParameter $stubbed)
    {
        $this->assertSame($original->getName(), $stubbed->getName());
        $this->assertSame($original->isArray(), $stubbed->isArray());
        $this->assertSame($original->isCallable(), $stubbed->isCallable());
        //$this->assertSame($original->allowsNull(), $stubbed->allowsNull()); @TODO WTF?
        $this->assertSame($original->canBePassedByValue(), $stubbed->canBePassedByValue());
        $this->assertSame($original->isOptional(), $stubbed->isOptional());
        $this->assertSame($original->isPassedByReference(), $stubbed->isPassedByReference());
        $this->assertSame($original->isVariadic(), $stubbed->isVariadic());

        if ($class = $original->getClass()) {
            $stubbedClass = $stubbed->getClass();

            $this->assertInstanceOf(ReflectionClass::class, $stubbedClass);
            $this->assertSame($class->getName(), $stubbedClass->getName());
        } else {
            $this->assertNull($stubbed->getClass());
        }
    }
}
