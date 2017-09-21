<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use Closure;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use RecursiveArrayIterator;
use ReflectionClass as CoreReflectionClass;
use ReflectionException;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionParameter as CoreReflectionParameter;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use const PHP_VERSION_ID;
use function array_filter;
use function array_map;
use function array_merge;
use function get_declared_classes;
use function get_declared_interfaces;
use function get_declared_traits;
use function in_array;
use function sort;
use function sprintf;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator
 */
class PhpInternalSourceLocatorTest extends TestCase
{
    /** @var Locator */
    private $astLocator;

    /** @var SourceStubber */
    private $sourceStubber;

    protected function setUp() : void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->astLocator    = $betterReflection->astLocator();
        $this->sourceStubber = $betterReflection->sourceStubber();
    }

    /**
     * @return Reflector|PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockReflector()
    {
        return $this->createMock(Reflector::class);
    }

    /**
     * @dataProvider internalSymbolsProvider
     */
    public function testCanFetchInternalLocatedSource(string $className) : void
    {
        $locator = new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber);

        try {
            /** @var ReflectionClass $reflection */
            $reflection = $locator->locateIdentifier(
                $this->getMockReflector(),
                new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
            );
            $source     = $reflection->getLocatedSource();

            self::assertInstanceOf(InternalLocatedSource::class, $source);
            self::assertNotEmpty($source->getSource());
        } catch (ReflectionException $e) {
            self::markTestIncomplete(sprintf(
                'Can\'t reflect class "%s" due to an internal reflection exception: "%s".',
                $className,
                $e->getMessage()
            ));
        }
    }

    /**
     * @throws ReflectionException
     *
     * @dataProvider internalSymbolsProvider
     */
    public function testCanReflectInternalClasses(string $className) : void
    {
        $phpInternalSourceLocator = new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber);
        $reflector                = new ClassReflector($phpInternalSourceLocator);

        $class = $reflector->reflect($className);

        self::assertInstanceOf(ReflectionClass::class, $class);
        self::assertSame($className, $class->getName());
        self::assertTrue($class->isInternal());
        self::assertFalse($class->isUserDefined());

        $internalReflection = new CoreReflectionClass($className);

        self::assertSame($internalReflection->isInterface(), $class->isInterface());
        self::assertSame($internalReflection->isTrait(), $class->isTrait());

        $this->assertSameClassAttributes($internalReflection, $class);
    }

    /**
     * @return string[][] internal symbols
     */
    public function internalSymbolsProvider() : array
    {
        $allSymbols = array_merge(
            get_declared_classes(),
            get_declared_interfaces(),
            get_declared_traits()
        );

        return array_map(
            static function (string $symbol) : array {
                return [$symbol];
            },
            array_filter(
                $allSymbols,
                static function (string $symbol) : bool {
                    $reflection = new CoreReflectionClass($symbol);

                    return $reflection->isInternal();
                }
            )
        );
    }

    public function testReturnsNullForNonExistentCode() : void
    {
        $locator = new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber);
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
        $locator = new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber);
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

    private function assertSameParentClass(CoreReflectionClass $original, ReflectionClass $stubbed) : void
    {
        $originalParentClass = $original->getParentClass();
        $stubbedParentClass  = $stubbed->getParentClass();

        self::assertSame(
            $originalParentClass ? $originalParentClass->getName() : null,
            $stubbedParentClass ? $stubbedParentClass->getName() : null
        );
    }

    private function assertSameInterfaces(CoreReflectionClass $original, ReflectionClass $stubbed) : void
    {
        $originalInterfacesNames = $original->getInterfaceNames();
        $stubbedInterfacesNames  = $stubbed->getInterfaceNames();

        sort($originalInterfacesNames);
        sort($stubbedInterfacesNames);

        self::assertSame($originalInterfacesNames, $stubbedInterfacesNames);
    }

    private function assertSameClassAttributes(CoreReflectionClass $original, ReflectionClass $stubbed) : void
    {
        self::assertSame($original->getName(), $stubbed->getName());

        $this->assertSameParentClass($original, $stubbed);
        $this->assertSameInterfaces($original, $stubbed);

        foreach ($original->getMethods() as $method) {
            $this->assertSameMethodAttributes($method, $stubbed->getMethod($method->getName()));
        }

        if ($original->getName() === RecursiveArrayIterator::class
            && (PHP_VERSION_ID < 70114 || (PHP_VERSION_ID >= 70200 && PHP_VERSION_ID < 70202))
        ) {
            // https://bugs.php.net/bug.php?id=75242
            self::markTestIncomplete(sprintf(
                'Constants of "%s" missing because of bug #75242.',
                $original->getName()
            ));
        }

        self::assertEquals($original->getConstants(), $stubbed->getConstants());
    }

    private function assertSameMethodAttributes(CoreReflectionMethod $original, ReflectionMethod $stubbed) : void
    {
        $originalParameterNames = array_map(
            static function (CoreReflectionParameter $parameter) : string {
                return $parameter->getDeclaringFunction()->getName() . '.' . $parameter->getName();
            },
            $original->getParameters()
        );
        $stubParameterNames     = array_map(
            static function (ReflectionParameter $parameter) : string {
                return $parameter->getDeclaringFunction()->getName() . '.' . $parameter->getName();
            },
            $stubbed->getParameters()
        );

        self::assertSame($originalParameterNames, $stubParameterNames);

        foreach ($original->getParameters() as $parameter) {
            $this->assertSameParameterAttributes(
                $original,
                $parameter,
                $stubbed->getParameter($parameter->getName())
            );
        }

        self::assertSame($original->isPublic(), $stubbed->isPublic());
        self::assertSame($original->isPrivate(), $stubbed->isPrivate());
        self::assertSame($original->isProtected(), $stubbed->isProtected());
        self::assertSame($original->returnsReference(), $stubbed->returnsReference());
        self::assertSame($original->isStatic(), $stubbed->isStatic());
        self::assertSame($original->isFinal(), $stubbed->isFinal());
    }

    private function assertSameParameterAttributes(
        CoreReflectionMethod $originalMethod,
        CoreReflectionParameter $original,
        ReflectionParameter $stubbed
    ) : void {
        $parameterName = $original->getDeclaringClass()->getName()
            . '#' . $originalMethod->getName()
            . '.' . $original->getName();

        self::assertSame($original->getName(), $stubbed->getName(), $parameterName);

        self::assertSame($original->isArray(), $stubbed->isArray(), $parameterName);
        if (! ($original->getDeclaringClass()->getName() === Closure::class && $originalMethod->getName() === 'fromCallable')) {
            // Bug in PHP: https://3v4l.org/EeHXS
            self::assertSame($original->isCallable(), $stubbed->isCallable(), $parameterName);
        }
        //self::assertSame($original->allowsNull(), $stubbed->allowsNull()); @TODO WTF?
        self::assertSame($original->canBePassedByValue(), $stubbed->canBePassedByValue(), $parameterName);
        if (! in_array($parameterName, ['mysqli_stmt#bind_param.vars', 'mysqli_stmt#bind_result.vars'], true)) {
            // Parameters are variadic but not optinal
            self::assertSame($original->isOptional(), $stubbed->isOptional(), $parameterName);
        }
        self::assertSame($original->isPassedByReference(), $stubbed->isPassedByReference(), $parameterName);
        self::assertSame($original->isVariadic(), $stubbed->isVariadic(), $parameterName);

        $class = $original->getClass();

        if ($class) {
            $stubbedClass = $stubbed->getClass();

            self::assertInstanceOf(ReflectionClass::class, $stubbedClass, $parameterName);
            self::assertSame($class->getName(), $stubbedClass->getName(), $parameterName);
        } else {
            self::assertNull($stubbed->getClass(), $parameterName);
        }
    }
}
