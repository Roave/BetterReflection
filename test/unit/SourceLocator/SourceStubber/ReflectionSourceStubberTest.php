<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\SourceStubber;

use ClassWithoutNamespaceForSourceStubber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException;
use ReflectionFunction as CoreReflectionFunction;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionParameter as CoreReflectionParameter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\ClassForSourceStubber;
use Roave\BetterReflectionTest\Fixture\ClassForSourceStubberWithDefaultStaticProperty;
use Roave\BetterReflectionTest\Fixture\EmptyTrait;
use Roave\BetterReflectionTest\Fixture\EnumBackedForSourceStubber;
use Roave\BetterReflectionTest\Fixture\EnumPureForSourceStubber;
use Roave\BetterReflectionTest\Fixture\InterfaceForSourceStubber;
use Roave\BetterReflectionTest\Fixture\PHP81ClassForSourceStubber;
use Roave\BetterReflectionTest\Fixture\PHP83ClassForSourceStubber;
use Roave\BetterReflectionTest\Fixture\PHP8ClassForSourceStubber;
use Roave\BetterReflectionTest\Fixture\TraitForSourceStubber;
use stdClass;
use Traversable;

use function array_filter;
use function array_map;
use function array_merge;
use function get_declared_classes;
use function get_declared_interfaces;
use function get_declared_traits;
use function get_defined_constants;
use function get_defined_functions;
use function in_array;
use function method_exists;
use function sort;

#[CoversClass(ReflectionSourceStubber::class)]
class ReflectionSourceStubberTest extends TestCase
{
    private const EXTENSIONS = ['Core', 'standard', 'pcre', 'SPL'];

    private ReflectionSourceStubber $stubber;

    private PhpInternalSourceLocator $phpInternalSourceLocator;

    private Reflector $reflector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stubber                  = new ReflectionSourceStubber();
        $this->phpInternalSourceLocator = new PhpInternalSourceLocator(
            BetterReflectionSingleton::instance()->astLocator(),
            $this->stubber,
        );
        $this->reflector                = new DefaultReflector($this->phpInternalSourceLocator);
    }

    public function testCanStubClass(): void
    {
        $stubData = $this->stubber->generateClassStub('stdClass');

        self::assertNotNull($stubData);
        self::assertStringMatchesFormat(
            '%Aclass stdClass%A{%A}%A',
            $stubData->getStub(),
        );
        self::assertSame('Core', $stubData->getExtensionName());
    }

    public function testCanStubInterface(): void
    {
        $stubData = $this->stubber->generateClassStub(Traversable::class);

        self::assertNotNull($stubData);
        self::assertStringMatchesFormat(
            '%Ainterface Traversable%A{%A}%A',
            $stubData->getStub(),
        );
        self::assertSame('Core', $stubData->getExtensionName());
    }

    public function testCanStubTraits(): void
    {
        require_once __DIR__ . '/../../Fixture/EmptyTrait.php';

        $stubData = $this->stubber->generateClassStub(EmptyTrait::class);

        self::assertNotNull($stubData);
        self::assertStringMatchesFormat(
            '%Atrait EmptyTrait%A{%A}%A',
            $stubData->getStub(),
        );
        self::assertNull($stubData->getExtensionName());
    }

    public function testClassStub(): void
    {
        require_once __DIR__ . '/../../Fixture/ClassForSourceStubber.php';

        $stubData = $this->stubber->generateClassStub(ClassForSourceStubber::class);

        self::assertNotNull($stubData);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/ClassForSourceStubberExpected.php', $stubData->getStub());
        self::assertNull($stubData->getExtensionName());
    }

    public function testPureEnumStub(): void
    {
        require_once __DIR__ . '/../../Fixture/EnumPureForSourceStubber.php';

        $stubData = $this->stubber->generateClassStub(EnumPureForSourceStubber::class);

        self::assertNotNull($stubData);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/EnumPureForSourceStubberExpected.php', $stubData->getStub());
        self::assertNull($stubData->getExtensionName());
    }

    public function testBackedEnumStub(): void
    {
        require_once __DIR__ . '/../../Fixture/EnumBackedForSourceStubber.php';

        $stubData = $this->stubber->generateClassStub(EnumBackedForSourceStubber::class);

        self::assertNotNull($stubData);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/EnumBackedForSourceStubberExpected.php', $stubData->getStub());
        self::assertNull($stubData->getExtensionName());
    }

    public function testUnknownClass(): void
    {
        /** @phpstan-var class-string $someClassName */
        $someClassName = 'SomeClass';
        self::assertNull($this->stubber->generateClassStub($someClassName));
    }

    public function testClassStubWithPHP8Syntax(): void
    {
        require_once __DIR__ . '/../../Fixture/PHP8ClassForSourceStubber.php';

        $stubData = $this->stubber->generateClassStub(PHP8ClassForSourceStubber::class);

        self::assertNotNull($stubData);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/PHP8ClassForSourceStubberExpected.php', $stubData->getStub());
        self::assertNull($stubData->getExtensionName());
    }

    public function testClassStubWithPHP81Syntax(): void
    {
        require_once __DIR__ . '/../../Fixture/PHP81ClassForSourceStubber.php';

        $stubData = $this->stubber->generateClassStub(PHP81ClassForSourceStubber::class);

        self::assertNotNull($stubData);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/PHP81ClassForSourceStubberExpected.php', $stubData->getStub());
        self::assertNull($stubData->getExtensionName());
    }

    #[RequiresPhp('8.3')]
    public function testClassStubWithTypedConstants(): void
    {
        require_once __DIR__ . '/../../Fixture/PHP83ClassForSourceStubber.php';

        $stubData = $this->stubber->generateClassStub(PHP83ClassForSourceStubber::class);

        self::assertNotNull($stubData);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/PHP83ClassForSourceStubberExpected.php', $stubData->getStub());
    }

    public function testClassWithoutNamespaceStub(): void
    {
        require_once __DIR__ . '/../../Fixture/ClassWithoutNamespaceForSourceStubber.php';

        $stubData = $this->stubber->generateClassStub(ClassWithoutNamespaceForSourceStubber::class);

        self::assertNotNull($stubData);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/ClassWithoutNamespaceForSourceStubberExpected.php', $stubData->getStub());
        self::assertNull($stubData->getExtensionName());
    }

    public function testClassStubWithDefaultStaticPropertyWithUnsupportedValue(): void
    {
        require_once __DIR__ . '/../../Fixture/ClassForSourceStubberWithDefaultStaticProperty.php';

        ClassForSourceStubberWithDefaultStaticProperty::$publicStaticProperty = new stdClass();

        $stubData = $this->stubber->generateClassStub(ClassForSourceStubberWithDefaultStaticProperty::class);

        self::assertNotNull($stubData);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/ClassForSourceStubberWithDefaultStaticPropertyExpected.php', $stubData->getStub());
    }

    public function testInterfaceStub(): void
    {
        require_once __DIR__ . '/../../Fixture/InterfaceForSourceStubber.php';

        $stubData = $this->stubber->generateClassStub(InterfaceForSourceStubber::class);

        self::assertNotNull($stubData);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/InterfaceForSourceStubberExpected.php', $stubData->getStub());
        self::assertNull($stubData->getExtensionName());
    }

    public function testTraitStub(): void
    {
        require_once __DIR__ . '/../../Fixture/TraitForSourceStubber.php';

        $stubData = $this->stubber->generateClassStub(TraitForSourceStubber::class);

        self::assertNotNull($stubData);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/TraitForSourceStubberExpected.php', $stubData->getStub());
        self::assertNull($stubData->getExtensionName());
    }

    public function testFunctionWithoutNamespaceStub(): void
    {
        require_once __DIR__ . '/../../Fixture/FunctionInNamespaceForSourceStubber.php';

        $stubData = $this->stubber->generateFunctionStub('Roave\BetterReflectionTest\Fixture\functionForSourceStubber');

        self::assertNotNull($stubData);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/FunctionInNamespaceForSourceStubberExpected.php', $stubData->getStub());
        self::assertNull($stubData->getExtensionName());
    }

    /** @return list<array{0: string}> */
    public static function internalClassesProvider(): array
    {
        $allSymbols = array_merge(
            get_declared_classes(),
            get_declared_interfaces(),
            get_declared_traits(),
        );

        return array_map(
            static fn (string $symbol): array => [$symbol],
            array_filter(
                $allSymbols,
                static function (string $symbol): bool {
                    $reflection = new CoreReflectionClass($symbol);

                    if (! $reflection->isInternal()) {
                        return false;
                    }

                    // Check only always enabled extensions
                    return in_array($reflection->getExtensionName(), self::EXTENSIONS, true);
                },
            ),
        );
    }

    /** @throws ReflectionException */
    #[DataProvider('internalClassesProvider')]
    public function testInternalClasses(string $className): void
    {
        $class = $this->reflector->reflectClass($className);

        self::assertInstanceOf(ReflectionClass::class, $class);
        self::assertSame($className, $class->getName());
        self::assertTrue($class->isInternal());
        self::assertFalse($class->isUserDefined());

        $internalReflection = new CoreReflectionClass($className);

        self::assertSame($internalReflection->isInterface(), $class->isInterface());
        self::assertSame($internalReflection->isTrait(), $class->isTrait());

        self::assertSameClassAttributes($internalReflection, $class);
    }

    private function assertSameParentClass(CoreReflectionClass $original, ReflectionClass $stubbed): void
    {
        $originalParentClass = $original->getParentClass();
        $stubbedParentClass  = $stubbed->getParentClass();

        self::assertSame(
            $originalParentClass ? $originalParentClass->getName() : null,
            $stubbedParentClass ? $stubbedParentClass->getName() : null,
        );
    }

    private function assertSameInterfaces(CoreReflectionClass $original, ReflectionClass $stubbed): void
    {
        $originalInterfacesNames = $original->getInterfaceNames();
        $stubbedInterfacesNames  = $stubbed->getInterfaceNames();

        sort($originalInterfacesNames);
        sort($stubbedInterfacesNames);

        self::assertSame($originalInterfacesNames, $stubbedInterfacesNames);
    }

    private function assertSameClassAttributes(CoreReflectionClass $original, ReflectionClass $stubbed): void
    {
        self::assertSame($original->getName(), $stubbed->getName());

        $this->assertSameParentClass($original, $stubbed);
        $this->assertSameInterfaces($original, $stubbed);

        foreach ($original->getMethods() as $method) {
            $this->assertSameMethodAttributes($method, $stubbed->getMethod($method->getName()));
        }

        $this->assertSameClassConstants($original, $stubbed);
    }

    private function assertSameClassConstants(CoreReflectionClass $original, ReflectionClass $stubbed): void
    {
        self::assertEquals(
            $original->getConstants(),
            array_map(static fn (ReflectionClassConstant $classConstant) => $classConstant->getValue(), $stubbed->getConstants()),
        );

        foreach ($original->getReflectionConstants() as $originalConstant) {
            if (
                ! method_exists($originalConstant, 'hasType')
                || ! method_exists($originalConstant, 'getType')
            ) {
                continue;
            }

            $stubbedConstant = $stubbed->getConstant($originalConstant->getName());

            self::assertSame($originalConstant->hasType(), $stubbedConstant->hasType());
            self::assertSame(
                (string) $originalConstant->getType(),
                (string) ReflectionType::fromTypeOrNull($stubbedConstant->getType()),
                $original->getName() . '::' . $originalConstant->getName(),
            );
        }
    }

    private function assertSameMethodAttributes(CoreReflectionMethod $original, ReflectionMethod $stubbed): void
    {
        $originalParameterNames = array_map(
            static fn (CoreReflectionParameter $parameter): string => $parameter->getDeclaringFunction()->getName() . '.' . $parameter->getName(),
            $original->getParameters(),
        );
        $stubParameterNames     = array_map(
            static fn (ReflectionParameter $parameter): string => $parameter->getDeclaringFunction()->getName() . '.' . $parameter->getName(),
            $stubbed->getParameters(),
        );

        self::assertSame($originalParameterNames, $stubParameterNames);

        foreach ($original->getParameters() as $parameter) {
            $this->assertSameParameterAttributes(
                $original,
                $parameter,
                $stubbed->getParameter($parameter->getName()),
            );
        }

        self::assertSame($original->isPublic(), $stubbed->isPublic());
        self::assertSame($original->isPrivate(), $stubbed->isPrivate());
        self::assertSame($original->isProtected(), $stubbed->isProtected());
        self::assertSame($original->returnsReference(), $stubbed->returnsReference());
        self::assertSame($original->isStatic(), $stubbed->isStatic());
        self::assertSame($original->isFinal(), $stubbed->isFinal());
        self::assertSame($original->isAbstract(), $stubbed->isAbstract());
        self::assertSame($original->isDeprecated(), $stubbed->isDeprecated());

        if (method_exists($original, 'hasTentativeReturnType')) {
            self::assertSame($original->hasTentativeReturnType(), $stubbed->hasTentativeReturnType(), $original->getName());
            self::assertSame(
                (string) $original->getTentativeReturnType(),
                (string) ReflectionType::fromTypeOrNull($stubbed->getTentativeReturnType()),
                $original->getName(),
            );
        }

        self::assertSame($original->hasReturnType(), $stubbed->hasReturnType(), $original->getName());
        self::assertSame(
            (string) $original->getReturnType(),
            (string) ReflectionType::fromTypeOrNull($stubbed->getReturnType()),
            $original->getName(),
        );
    }

    private function assertSameParameterAttributes(
        CoreReflectionMethod $originalMethod,
        CoreReflectionParameter $original,
        ReflectionParameter $stubbed,
    ): void {
        $methodName    = $original->getDeclaringClass()->getName() . '#' . $originalMethod->getName();
        $parameterName = $methodName . '.' . $original->getName();

        self::assertSame($original->getName(), $stubbed->getName(), $parameterName);

        if ($original->isDefaultValueAvailable()) {
            self::assertSame($original->getDefaultValue(), $stubbed->getDefaultValue(), $parameterName);
        } else {
            self::assertSame($original->isDefaultValueAvailable(), $stubbed->isDefaultValueAvailable(), $parameterName);
        }

        //self::assertSame($original->allowsNull(), $stubbed->allowsNull()); @TODO WTF?

        self::assertSame($original->canBePassedByValue(), $stubbed->canBePassedByValue(), $parameterName);
        self::assertSame($original->isOptional(), $stubbed->isOptional(), $parameterName);
        self::assertSame($original->isPassedByReference(), $stubbed->isPassedByReference(), $parameterName);
        self::assertSame($original->isVariadic(), $stubbed->isVariadic(), $parameterName);
    }

    /** @return list<array{0: string}> */
    public static function internalFunctionsProvider(): array
    {
        /** @var list<string> $functionNames */
        $functionNames = get_defined_functions()['internal'];

        return array_map(
            static fn (string $functionName): array => [$functionName],
            array_filter(
                $functionNames,
                static function (string $functionName): bool {
                    $reflection = new CoreReflectionFunction($functionName);

                    return $reflection->isInternal();
                },
            ),
        );
    }

    #[DataProvider('internalFunctionsProvider')]
    public function testInternalFunctionsReturnType(string $functionName): void
    {
        $stubbedReflection  = $this->reflector->reflectFunction($functionName);
        $originalReflection = new CoreReflectionFunction($functionName);

        if (method_exists($originalReflection, 'hasTentativeReturnType') && $originalReflection->hasTentativeReturnType()) {
            self::assertSame($originalReflection->hasTentativeReturnType(), $stubbedReflection->hasTentativeReturnType());
            self::assertSame(
                (string) $originalReflection->getTentativeReturnType(),
                (string) ReflectionType::fromTypeOrNull($stubbedReflection->getTentativeReturnType()),
            );
        } else {
            self::assertSame($originalReflection->hasReturnType(), $stubbedReflection->hasReturnType());
            self::assertSame(
                (string) $originalReflection->getReturnType(),
                (string) ReflectionType::fromTypeOrNull($stubbedReflection->getReturnType()),
            );
        }
    }

    public function testFunctionWithParameterPassedByReference(): void
    {
        $functionReflection = $this->reflector->reflectFunction('sort');

        self::assertSame('sort', $functionReflection->getName());
        self::assertSame(2, $functionReflection->getNumberOfParameters());

        $parameterReflection = $functionReflection->getParameters()[0];
        self::assertSame('array', $parameterReflection->getName());
        self::assertFalse($parameterReflection->isOptional());
        self::assertTrue($parameterReflection->isPassedByReference());
        self::assertFalse($parameterReflection->canBePassedByValue());
    }

    public function testFunctionWithOptionalParameter(): void
    {
        $functionReflection = $this->reflector->reflectFunction('preg_match');

        self::assertSame('preg_match', $functionReflection->getName());
        self::assertSame(5, $functionReflection->getNumberOfParameters());
        self::assertSame(2, $functionReflection->getNumberOfRequiredParameters());

        $parameterReflection = $functionReflection->getParameters()[2];
        self::assertSame('matches', $parameterReflection->getName());
        self::assertTrue($parameterReflection->isOptional());
    }

    /** @return list<array{0: string, 1: int, 2: bool, 3: bool}> */
    public static function variadicParametersProvider(): array
    {
        return [
            ['sprintf', 1, true, true],
            ['printf', 1, true, true],
        ];
    }

    #[DataProvider('variadicParametersProvider')]
    public function testFunctionWithVariadicParameter(string $functionName, int $parameterPosition, bool $parameterIsVariadic, bool $parameterIsOptional): void
    {
        $functionReflection = $this->reflector->reflectFunction($functionName);

        self::assertSame($functionName, $functionReflection->getName());

        $parametersReflections = $functionReflection->getParameters();
        self::assertArrayHasKey($parameterPosition, $parametersReflections);
        self::assertSame($parameterIsVariadic, $parametersReflections[$parameterPosition]->isVariadic());
        self::assertSame($parameterIsOptional, $parametersReflections[$parameterPosition]->isOptional());
    }

    public function testUnknownFunction(): void
    {
        self::assertNull($this->stubber->generateFunctionStub('someFunction'));
    }

    public function testCanStubConstant(): void
    {
        $stubData = $this->stubber->generateConstantStub('E_ALL');

        self::assertNotNull($stubData);
        self::assertStringMatchesFormat(
            "%Adefine('E_ALL',%w%d);",
            $stubData->getStub(),
        );
        self::assertSame('Core', $stubData->getExtensionName());
    }

    /** @return list<list<mixed>> */
    public static function internalConstantsProvider(): array
    {
        $provider = [];

        /** @var array<string, array<string, int|string|float|bool|mixed[]|resource|null>> $constants */
        $constants = get_defined_constants(true);

        foreach ($constants as $extensionName => $extensionConstants) {
            // Check only always enabled extensions
            if (! in_array($extensionName, self::EXTENSIONS, true)) {
                continue;
            }

            foreach ($extensionConstants as $constantName => $constantValue) {
                $provider[] = [$constantName, $constantValue, $extensionName];
            }
        }

        return $provider;
    }

    #[DataProvider('internalConstantsProvider')]
    public function testInternalConstants(string $constantName, mixed $constantValue, string $extensionName): void
    {
        $constantReflection = $this->reflector->reflectConstant($constantName);

        self::assertInstanceOf(ReflectionConstant::class, $constantReflection);
        self::assertSame($constantName, $constantReflection->getName());
        self::assertSame($constantName, $constantReflection->getShortName());

        self::assertNull($constantReflection->getNamespaceName());
        self::assertFalse($constantReflection->inNamespace());
        self::assertTrue($constantReflection->isInternal());
        self::assertFalse($constantReflection->isUserDefined());
        self::assertSame($extensionName, $constantReflection->getExtensionName());

        // NAN cannot be compared
        if ($constantName === 'NAN') {
            return;
        }

        self::assertSame($constantValue, $constantReflection->getValue());
    }

    public function testUnknownConstant(): void
    {
        self::assertNull($this->stubber->generateConstantStub('SOME_CONSTANT'));
    }
}
