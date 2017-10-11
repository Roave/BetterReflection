<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\SourceLocator\Type;

use Closure;
use DOMNamedNodeMap;
use IntlChar;
use IntlGregorianCalendar;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionParameter as CoreReflectionParameter;
use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Reflection\ReflectionMethod;
use Rector\BetterReflection\Reflection\ReflectionParameter;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\Reflector\Reflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Located\InternalLocatedSource;
use Rector\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Rector\BetterReflectionTest\BetterReflectionSingleton;
use ZipArchive;

/**
 * @covers \Rector\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator
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
        $className = $original->getName();

        if (\PHP_VERSION_ID < 70200 && \in_array($className, [DOMNamedNodeMap::class, ZipArchive::class], true)) {
            self::markTestSkipped(\sprintf('"%s" changed between PHP 7.1 and PHP 7.2', $className));
        }

        $originalInterfacesNames = $original->getInterfaceNames();
        $stubbedInterfacesNames  = $stubbed->getInterfaceNames();

        \sort($originalInterfacesNames);
        \sort($stubbedInterfacesNames);

        self::assertSame($originalInterfacesNames, $stubbedInterfacesNames);
    }

    private function assertSameClassAttributes(CoreReflectionClass $original, ReflectionClass $stubbed) : void
    {
        self::assertSame($original->getName(), $stubbed->getName());

        $this->assertSameParentClass($original, $stubbed);
        $this->assertSameInterfaces($original, $stubbed);

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

        if (Closure::class === $original->getName()) {
            // https://bugs.php.net/bug.php?id=75186
        } else {
            self::assertSame($originalMethodNames, $stubbedMethodNames);
        }

        foreach ($originalMethods as $method) {
            $this->assertSameMethodAttributes($method, $stubbed->getMethod($method->getName()));
        }

        if (\in_array($original->getName(), [IntlGregorianCalendar::class, IntlChar::class], true)) {
            self::markTestSkipped(\sprintf(
                'Constants for "%s" change depending on environment: not testing them, as it\'s a suicide',
                $original->getName()
            ));
        }

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
        if (Closure::class === $original->getDeclaringClass()->getName() && 'fromCallable' === $originalMethod->getName()) {
            // Bug in PHP: https://3v4l.org/EeHXS
        } else {
            self::assertSame($original->isCallable(), $stubbed->isCallable(), $parameterName);
        }
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
