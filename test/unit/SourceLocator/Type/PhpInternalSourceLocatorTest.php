<?php

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use ReflectionClass as PhpReflectionClass;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator
 */
class PhpInternalSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Reflector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockReflector()
    {
        return $this->createMock(Reflector::class);
    }

    /**
     * @dataProvider internalSymbolsProvider
     *
     * @param string $className
     */
    public function testCanFetchInternalLocatedSource(string $className) : void
    {
        $locator = new PhpInternalSourceLocator();

        try {
            /** @var ReflectionClass $reflection */
            $reflection = $locator->locateIdentifier(
                $this->getMockReflector(),
                new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
            );
            $source = $reflection->getLocatedSource();

            self::assertInstanceOf(InternalLocatedSource::class, $source);
            self::assertNotEmpty($source->getSource());
        } catch (\ReflectionException $e) {
            self::markTestIncomplete(sprintf(
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
    public function testCanReflectInternalClasses(string $className) : void
    {
        /* @var $class */
        $phpInternalSourceLocator = new PhpInternalSourceLocator();
        $reflector = new ClassReflector($phpInternalSourceLocator);

        try {
            $class = $reflector->reflect($className);
        } catch (\ReflectionException $e) {
            if ($phpInternalSourceLocator->hasStub($className)) {
                throw $e;
            }

            self::markTestIncomplete(sprintf(
                'Can\'t reflect class "%s" due to an internal reflection exception: "%s". Consider adding a stub class',
                $className,
                $e->getMessage()
            ));
        }

        self::assertInstanceOf(ReflectionClass::class, $class);
        self::assertSame($className, $class->getName());
        self::assertTrue($class->isInternal());
        self::assertFalse($class->isUserDefined());

        $internalReflection = new \ReflectionClass($className);

        self::assertSame($internalReflection->isInterface(), $class->isInterface());
        self::assertSame($internalReflection->isTrait(), $class->isTrait());
    }

    /**
     * @return string[] internal symbols
     */
    public function internalSymbolsProvider() : array
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

    public function testReturnsNullForNonExistentCode() : void
    {
        $locator = new PhpInternalSourceLocator();
        self::assertNull(
            $locator->locateIdentifier(
                $this->getMockReflector(),
                new Identifier(
                    'Foo\Bar',
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
                )
            )
        );
    }

    public function testReturnsNullForFunctions() : void
    {
        $locator = new PhpInternalSourceLocator();
        self::assertNull(
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
     *
     * @coversNothing
     */
    public function testAllGeneratedStubsAreInSyncWithInternalReflectionClasses(string $className) : void
    {
        if (! (
            class_exists($className, false)
            || interface_exists($className, false)
            || trait_exists($className, false)
        )) {
            $this->markTestSkipped(sprintf('Class "%s" is not available in this environment', $className));
        }

        $reflector = new ClassReflector(new PhpInternalSourceLocator());

        self::assertSameClassAttributes(new \ReflectionClass($className), $reflector->reflect($className));
    }

    /**
     * @return string[][]
     */
    public function stubbedClassesProvider() : array
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

    private function assertSameClassAttributes(\ReflectionClass $original, ReflectionClass $stubbed) : void
    {
        self::assertSame($original->getName(), $stubbed->getName());

        $internalParent     = $original->getParentClass();
        $betterParent       = $stubbed->getParentClass();
        $internalParentName = $internalParent ? $internalParent->getName() : null;
        $betterParentName   = $betterParent ? $betterParent->getName() : null;

        self::assertSame($internalParentName, $betterParentName);

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

        self::assertSame($originalMethodNames, $stubbedMethodNames);
        self::assertEquals($original->getConstants(), $stubbed->getConstants());

        foreach ($originalMethods as $method) {
            self::assertSameMethodAttributes($method, $stubbed->getMethod($method->getName()));
        }
    }

    private function assertSameMethodAttributes(\ReflectionMethod $original, ReflectionMethod $stubbed) : void
    {
        self::assertSame(
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
            self::assertSameParameterAttributes($parameter, $stubbed->getParameter($parameter->getName()));
        }

        self::assertSame($original->isPublic(), $stubbed->isPublic());
        self::assertSame($original->isPrivate(), $stubbed->isPrivate());
        self::assertSame($original->isProtected(), $stubbed->isProtected());
        self::assertSame($original->returnsReference(), $stubbed->returnsReference());
        self::assertSame($original->isStatic(), $stubbed->isStatic());
        self::assertSame($original->isFinal(), $stubbed->isFinal());
    }

    private function assertSameParameterAttributes(\ReflectionParameter $original, ReflectionParameter $stubbed) : void
    {
        self::assertSame($original->getName(), $stubbed->getName());
        self::assertSame($original->isArray(), $stubbed->isArray());
        self::assertSame($original->isCallable(), $stubbed->isCallable());
        //self::assertSame($original->allowsNull(), $stubbed->allowsNull()); @TODO WTF?
        self::assertSame($original->canBePassedByValue(), $stubbed->canBePassedByValue());
        self::assertSame($original->isOptional(), $stubbed->isOptional());
        self::assertSame($original->isPassedByReference(), $stubbed->isPassedByReference());
        self::assertSame($original->isVariadic(), $stubbed->isVariadic());

        if ($class = $original->getClass()) {
            $stubbedClass = $stubbed->getClass();

            self::assertInstanceOf(ReflectionClass::class, $stubbedClass);
            self::assertSame($class->getName(), $stubbedClass->getName());
        } else {
            self::assertNull($stubbed->getClass());
        }
    }
}
