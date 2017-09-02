<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use IntlChar;
use IntlGregorianCalendar;
use PHPUnit\Framework\TestCase;
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
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator
 */
class PhpInternalSourceLocatorTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

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
        $locator = new PhpInternalSourceLocator($this->astLocator);

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
            self::markTestIncomplete(\sprintf(
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
        $phpInternalSourceLocator = new PhpInternalSourceLocator($this->astLocator);
        $reflector                = new ClassReflector($phpInternalSourceLocator);

        try {
            $class = $reflector->reflect($className);
        } catch (ReflectionException $e) {
            if ($phpInternalSourceLocator->hasStub($className)) {
                throw $e;
            }

            self::markTestIncomplete(\sprintf(
                'Can\'t reflect class "%s" due to an internal reflection exception: "%s". Consider adding a stub class',
                $className,
                $e->getMessage()
            ));
        }

        self::assertInstanceOf(ReflectionClass::class, $class);
        self::assertSame($className, $class->getName());
        self::assertTrue($class->isInternal());
        self::assertFalse($class->isUserDefined());

        $internalReflection = new CoreReflectionClass($className);

        self::assertSame($internalReflection->isInterface(), $class->isInterface());
        self::assertSame($internalReflection->isTrait(), $class->isTrait());
    }

    /**
     * @return string[] internal symbols
     */
    public function internalSymbolsProvider() : array
    {
        $allSymbols = \array_merge(
            \get_declared_classes(),
            \get_declared_interfaces(),
            \get_declared_traits()
        );

        $indexedSymbols = \array_combine($allSymbols, $allSymbols);

        return \array_map(
            function (string $symbol) : array {
                return [$symbol];
            },
            \array_filter(
                $indexedSymbols,
                function (string $symbol) : bool {
                    $reflection = new CoreReflectionClass($symbol);

                    return $reflection->isInternal();
                }
            )
        );
    }

    public function testReturnsNullForNonExistentCode() : void
    {
        $locator = new PhpInternalSourceLocator($this->astLocator);
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
        $locator = new PhpInternalSourceLocator($this->astLocator);
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
        if ( ! (
            \class_exists($className, false)
            || \interface_exists($className, false)
            || \trait_exists($className, false)
        )) {
            $this->markTestSkipped(\sprintf('Class "%s" is not available in this environment', $className));
        }

        $reflector = new ClassReflector(new PhpInternalSourceLocator($this->astLocator));

        $this->assertSameClassAttributes(new CoreReflectionClass($className), $reflector->reflect($className));
    }

    /**
     * @return string[][]
     */
    public function stubbedClassesProvider() : array
    {
        $classNames = \array_filter(
            \str_replace('.stub', '', \scandir(__DIR__ . '/../../../../stub', 0)),
            function (string $fileName) : string {
                return \trim($fileName, '.');
            }
        );

        return \array_combine(
            $classNames,
            \array_map(
                function (string $fileName) : array {
                    return [$fileName];
                },
                $classNames
            )
        );
    }

    private function assertSameClassAttributes(CoreReflectionClass $original, ReflectionClass $stubbed) : void
    {
        self::assertSame($original->getName(), $stubbed->getName());

        $internalParent     = $original->getParentClass();
        $betterParent       = $stubbed->getParentClass();
        $internalParentName = $internalParent ? $internalParent->getName() : null;
        $betterParentName   = $betterParent ? $betterParent->getName() : null;

        self::assertSame($internalParentName, $betterParentName);

        $originalMethods = $original->getMethods();

        $originalMethodNames = \array_map(
            function (CoreReflectionMethod $method) : string {
                return $method->getName();
            },
            $originalMethods
        );

        $stubbedMethodNames = \array_map(
            function (ReflectionMethod $method) : string {
                return $method->getName();
            },
            $stubbed->getMethods() // @TODO see #107
        );

        \sort($originalMethodNames);
        \sort($stubbedMethodNames);

        if (\count($originalMethodNames) > \count($stubbedMethodNames)) {
            self::markTestIncomplete(\sprintf(
                'New methods detected in class "%s" which are not present in the stubs: %s',
                $original->getName(),
                "\n\n" . \implode("\n", \array_diff($originalMethodNames, $stubbedMethodNames))
            ));
        }

        self::assertSame($originalMethodNames, $stubbedMethodNames);

        foreach ($originalMethods as $method) {
            $this->assertSameMethodAttributes($method, $stubbed->getMethod($method->getName()));
        }

        if (\in_array($original->getName(), [IntlGregorianCalendar::class, IntlChar::class], true)) {
            self::markTestSkipped(\sprintf(
                'Constants for "%s" change depending on environment: not testing them, as it\'s a suicide',
                $original->getName()
            ));
        }

        // See https://bugs.php.net/bug.php?id=75090
        if ($original->getName() !== IntlGregorianCalendar::class) {
            $originalConstants = $original->getConstants();
            $stubConstants     = $stubbed->getConstants();

            if (\count($originalConstants) > \count($stubConstants)) {
                self::markTestIncomplete(\sprintf(
                    'New constants detected in class "%s" which are not present in the stubs: %s',
                    $original->getName(),
                    "\n\n" . \implode("\n", \array_diff($originalConstants, $stubConstants))
                ));
            }

            self::assertEquals($original->getConstants(), $stubbed->getConstants());
        }
    }

    private function assertSameMethodAttributes(CoreReflectionMethod $original, ReflectionMethod $stubbed) : void
    {
        $originalParameterNames = \array_map(
            function (CoreReflectionParameter $parameter) : string {
                return $parameter->getDeclaringFunction()->getName() . '.' . $parameter->getName();
            },
            $original->getParameters()
        );
        $stubParameterNames = \array_map(
            function (ReflectionParameter $parameter) : string {
                return $parameter->getDeclaringFunction()->getName() . '.' . $parameter->getName();
            },
            $stubbed->getParameters()
        );

        if (\count($originalParameterNames) > \count($stubParameterNames)) {
            self::markTestIncomplete(\sprintf(
                'New parameters found in method "%s#%s": %s',
                $original->getDeclaringClass()->getName(),
                $original->getName(),
                "\n" . \implode("\n", \array_diff($originalParameterNames, $stubParameterNames))
            ));
        }

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

        if (\PHP_VERSION_ID < 70200
            && \in_array(
                $parameterName,
                ['DateTime#createFromFormat.object', 'DateTimeImmutable#createFromFormat.object'],
                true
            )
        ) {
            self::markTestSkipped('New type hints were introduced in PHP 7.2 for ' . $parameterName);
        }

        self::assertSame($original->getName(), $stubbed->getName(), $parameterName);
        self::assertSame($original->isArray(), $stubbed->isArray(), $parameterName);
        self::assertSame($original->isCallable(), $stubbed->isCallable(), $parameterName);
        //self::assertSame($original->allowsNull(), $stubbed->allowsNull()); @TODO WTF?
        self::assertSame($original->canBePassedByValue(), $stubbed->canBePassedByValue(), $parameterName);
        self::assertSame($original->isOptional(), $stubbed->isOptional(), $parameterName);
        self::assertSame($original->isPassedByReference(), $stubbed->isPassedByReference(), $parameterName);
        self::assertSame($original->isVariadic(), $stubbed->isVariadic(), $parameterName);

        if ($class = $original->getClass()) {
            $stubbedClass = $stubbed->getClass();

            self::assertInstanceOf(ReflectionClass::class, $stubbedClass, $parameterName);
            self::assertSame($class->getName(), $stubbedClass->getName(), $parameterName);
        } else {
            self::assertNull($stubbed->getClass(), $parameterName);
        }
    }
}
