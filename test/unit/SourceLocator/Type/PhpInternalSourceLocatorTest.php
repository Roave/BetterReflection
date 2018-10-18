<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use Closure;
use DOMNamedNodeMap;
use IntlChar;
use IntlGregorianCalendar;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
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
use ZipArchive;
use const PHP_VERSION_ID;
use function array_combine;
use function array_diff;
use function array_filter;
use function array_map;
use function array_merge;
use function class_exists;
use function count;
use function get_declared_classes;
use function get_declared_interfaces;
use function get_declared_traits;
use function implode;
use function in_array;
use function interface_exists;
use function scandir;
use function sort;
use function sprintf;
use function str_replace;
use function trait_exists;
use function trim;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator
 */
class PhpInternalSourceLocatorTest extends TestCase
{
    /** @var Locator */
    private $astLocator;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
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
            self::markTestIncomplete(sprintf(
                'Can\'t reflect class "%s" due to an internal reflection exception: "%s". Consider adding a stub class',
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
        $phpInternalSourceLocator = new PhpInternalSourceLocator($this->astLocator);
        $reflector                = new ClassReflector($phpInternalSourceLocator);

        try {
            $class = $reflector->reflect($className);
        } catch (ReflectionException $e) {
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

        $internalReflection = new CoreReflectionClass($className);

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
            static function (string $symbol) : array {
                return [$symbol];
            },
            array_filter(
                $indexedSymbols,
                static function (string $symbol) : bool {
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

        $reflector = new ClassReflector(new PhpInternalSourceLocator($this->astLocator));

        $this->assertSameClassAttributes(new CoreReflectionClass($className), $reflector->reflect($className));
    }

    /**
     * @return string[][]
     */
    public function stubbedClassesProvider() : array
    {
        $classNames = array_filter(
            str_replace('.stub', '', scandir(__DIR__ . '/../../../../stub', 0)),
            static function (string $fileName) : string {
                return trim($fileName, '.');
            }
        );

        return array_combine(
            $classNames,
            array_map(
                static function (string $fileName) : array {
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

        if (PHP_VERSION_ID < 70200 && in_array($className, [DOMNamedNodeMap::class, ZipArchive::class], true)) {
            self::markTestSkipped(sprintf('"%s" changed between PHP 7.1 and PHP 7.2', $className));
        }

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

        $originalMethods = $original->getMethods();

        $originalMethodNames = array_map(
            static function (CoreReflectionMethod $method) : string {
                return $method->getName();
            },
            $originalMethods
        );

        $stubbedMethodNames = array_map(
            static function (ReflectionMethod $method) : string {
                return $method->getName();
            },
            $stubbed->getMethods() // @TODO see #107
        );

        sort($originalMethodNames);
        sort($stubbedMethodNames);

        if (count($originalMethodNames) > count($stubbedMethodNames)) {
            self::markTestIncomplete(sprintf(
                'New methods detected in class "%s" which are not present in the stubs: %s',
                $original->getName(),
                "\n\n" . implode("\n", array_diff($originalMethodNames, $stubbedMethodNames))
            ));
        }

        if ($original->getName() !== Closure::class) {
            // https://bugs.php.net/bug.php?id=75186
            self::assertSame($originalMethodNames, $stubbedMethodNames);
        }

        foreach ($originalMethods as $method) {
            $this->assertSameMethodAttributes($method, $stubbed->getMethod($method->getName()));
        }

        if (in_array($original->getName(), [IntlGregorianCalendar::class, IntlChar::class], true)) {
            self::markTestSkipped(sprintf(
                'Constants for "%s" change depending on environment: not testing them, as it\'s a suicide',
                $original->getName()
            ));
        }

        $originalConstants = $original->getConstants();
        $stubConstants     = $stubbed->getConstants();

        if (count($originalConstants) > count($stubConstants)) {
            self::markTestIncomplete(sprintf(
                'New constants detected in class "%s" which are not present in the stubs: %s',
                $original->getName(),
                "\n\n" . implode("\n", array_diff($originalConstants, $stubConstants))
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

        $methodName = sprintf('%s#%s', $original->getDeclaringClass()->getName(), $original->getName());

        if (count($originalParameterNames) > count($stubParameterNames)) {
            self::markTestIncomplete(sprintf(
                'New parameters found in method "%s": %s',
                $methodName,
                "\n" . implode("\n", array_diff($originalParameterNames, $stubParameterNames))
            ));
        }

        if ((PHP_VERSION_ID < 70117 || (PHP_VERSION_ID >= 70200 && PHP_VERSION_ID < 70205))
            && in_array(
                $methodName,
                [
                    'DateTime#__construct',
                    'DateTimeImmutable#__construct',
                ],
                true
            )
        ) {
            self::markTestSkipped('Argument name was changed in PHP 7.1.17 and 7.2.5 for ' . $methodName);
        } else {
            self::assertSame($originalParameterNames, $stubParameterNames);
        }

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

        if (PHP_VERSION_ID < 70200
            && in_array(
                $parameterName,
                [
                    'DateTime#createFromFormat.object',
                    'DateTimeImmutable#createFromFormat.object',
                    'UConverter#getAliases.name',
                ],
                true
            )
        ) {
            self::markTestSkipped('New type hints were introduced in PHP 7.2 for ' . $parameterName);
        }

        if (in_array(
            $parameterName,
            [
                'DateTime#__construct.object',
                'DateTimeImmutable#__construct.object',
            ],
            true
        )) {
            self::markTestSkipped('Argument name was changed in PHP 7.1.17 and 7.2.5 for ' . $parameterName);
        } else {
            self::assertSame($original->getName(), $stubbed->getName(), $parameterName);
        }

        self::assertSame($original->isArray(), $stubbed->isArray(), $parameterName);
        if (! ($original->getDeclaringClass()->getName() === Closure::class && $originalMethod->getName() === 'fromCallable')) {
            // Bug in PHP: https://3v4l.org/EeHXS
            self::assertSame($original->isCallable(), $stubbed->isCallable(), $parameterName);
        }
        //self::assertSame($original->allowsNull(), $stubbed->allowsNull()); @TODO WTF?
        self::assertSame($original->canBePassedByValue(), $stubbed->canBePassedByValue(), $parameterName);

        if (! ($parameterName === 'SoapClient#__setSoapHeaders.soapheaders' && PHP_VERSION_ID < 70112)) {
            // https://bugs.php.net/bug.php?id=75464 fixed in PHP 7.1.2
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
