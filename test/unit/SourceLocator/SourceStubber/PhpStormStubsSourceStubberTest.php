<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\SourceStubber;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionFunction as CoreReflectionFunction;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionParameter as CoreReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\ConstantReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

use function array_filter;
use function array_map;
use function array_merge;
use function get_declared_classes;
use function get_declared_interfaces;
use function get_declared_traits;
use function get_defined_constants;
use function get_defined_functions;
use function in_array;
use function sort;
use function sprintf;

/**
 * @covers \Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber
 */
class PhpStormStubsSourceStubberTest extends TestCase
{
    private const EXTENSIONS = ['Core', 'standard', 'pcre', 'SPL'];

    private PhpStormStubsSourceStubber $sourceStubber;

    private PhpInternalSourceLocator $phpInternalSourceLocator;

    private ClassReflector $classReflector;

    private FunctionReflector $functionReflector;

    private ConstantReflector $constantReflector;

    protected function setUp(): void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->sourceStubber            = new PhpStormStubsSourceStubber($betterReflection->phpParser());
        $this->phpInternalSourceLocator = new PhpInternalSourceLocator(
            $betterReflection->astLocator(),
            $this->sourceStubber,
        );
        $this->classReflector           = new ClassReflector($this->phpInternalSourceLocator);
        $this->functionReflector        = new FunctionReflector($this->phpInternalSourceLocator, $this->classReflector);
        $this->constantReflector        = new ConstantReflector($this->phpInternalSourceLocator, $this->classReflector);
    }

    /**
     * @return string[][]
     */
    public function internalClassesProvider(): array
    {
        $classNames = array_merge(
            get_declared_classes(),
            get_declared_interfaces(),
            get_declared_traits(),
        );

        return array_map(
            static fn (string $className): array => [$className],
            array_filter(
                $classNames,
                static function (string $className): bool {
                    $reflection = new CoreReflectionClass($className);

                    if (! $reflection->isInternal()) {
                        return false;
                    }

                    // Check only always enabled extensions
                    return in_array($reflection->getExtensionName(), self::EXTENSIONS, true);
                },
            ),
        );
    }

    /**
     * @dataProvider internalClassesProvider
     */
    public function testInternalClasses(string $className): void
    {
        $class = $this->classReflector->reflect($className);

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

        // Needs fix in JetBrains/phpstorm-stubs
        if ($original->getName() !== 'SplFixedArray') {
            $this->assertSameInterfaces($original, $stubbed);
        }

        foreach ($original->getMethods() as $method) {
            // Needs fix in JetBrains/phpstorm-stubs
            if ($original->getName() === 'Generator' && $method->getName() === 'throw') {
                continue;
            }

            $this->assertSameMethodAttributes($method, $stubbed->getMethod($method->getName()));
        }

        self::assertEquals($original->getConstants(), $stubbed->getConstants());
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

        $methodName = $original->getDeclaringClass()->getName() . '#' . $original->getName();

        self::assertSame($original->isPublic(), $stubbed->isPublic());
        self::assertSame($original->isPrivate(), $stubbed->isPrivate());
        self::assertSame($original->isProtected(), $stubbed->isProtected());
        self::assertSame($original->returnsReference(), $stubbed->returnsReference());
        self::assertSame($original->isStatic(), $stubbed->isStatic());
        self::assertSame($original->isFinal(), $stubbed->isFinal());

        // Needs fixes in JetBrains/phpstorm-stubs
        if (
            in_array($methodName, [
                'Closure#__invoke',
                'Directory#read',
                'Directory#rewind',
                'Directory#close',
                'WeakReference#create',
            ], true)
        ) {
            return;
        }

        self::assertSame($originalParameterNames, $stubParameterNames);

        foreach ($original->getParameters() as $parameter) {
            $stubbedParameter = $stubbed->getParameter($parameter->getName());

            $this->assertSameParameterAttributes(
                $original,
                $parameter,
                $stubbedParameter,
            );
        }
    }

    private function assertSameParameterAttributes(
        CoreReflectionMethod $originalMethod,
        CoreReflectionParameter $original,
        ReflectionParameter $stubbed,
    ): void {
        $parameterName = $original->getDeclaringClass()->getName()
            . '#' . $originalMethod->getName()
            . '.' . $original->getName();

        self::assertSame($original->getName(), $stubbed->getName(), $parameterName);

        // Needs fixes in JetBrains/phpstorm-stubs
        if ($parameterName !== 'SplFixedArray#fromArray.array') {
            // @ because isArray() is deprecated
            self::assertSame(@$original->isArray(), $stubbed->isArray(), $parameterName);
        }

        if (
            ! in_array($parameterName, [
                'ArrayObject#uasort.callback',
                'ArrayObject#uksort.callback',
                'ArrayIterator#uasort.callback',
                'ArrayIterator#uksort.callback',
                'RecursiveCallbackFilterIterator#__construct.callback',
            ], true)
        ) {
            // @ because isCallable() is deprecated
            self::assertSame(@$original->isCallable(), $stubbed->isCallable(), $parameterName);
        }

        self::assertSame($original->canBePassedByValue(), $stubbed->canBePassedByValue(), $parameterName);
        // Bugs in PHP
        if (
            ! in_array($parameterName, [
                'FilesystemIterator#setFlags.flags',
                'RecursiveIteratorIterator#getSubIterator.level',
            ], true)
        ) {
            self::assertSame($original->isOptional(), $stubbed->isOptional(), $parameterName);
        }

        self::assertSame($original->isPassedByReference(), $stubbed->isPassedByReference(), $parameterName);
        self::assertSame($original->isVariadic(), $stubbed->isVariadic(), $parameterName);

        // @ because getClass() is deprecated
        $class = @$original->getClass();
        if ($class) {
            $stubbedClass = $stubbed->getClass();

            // Needs fixes in JetBrains/phpstorm-stubs
            if (
                ! in_array($parameterName, [
                    'ErrorException#__construct.previous',
                    'SplObjectStorage#addAll.storage',
                    'SplObjectStorage#removeAll.storage',
                    'SplObjectStorage#removeAllExcept.storage',
                ], true)
            ) {
                self::assertInstanceOf(ReflectionClass::class, $stubbedClass, $parameterName);
                self::assertSame($class->getName(), $stubbedClass->getName(), $parameterName);
            }
        } else {
            self::assertNull($stubbed->getClass(), $parameterName);
        }
    }

    /**
     * @return string[][]
     */
    public function internalFunctionsProvider(): array
    {
        $functionNames = get_defined_functions()['internal'];

        return array_map(
            static fn (string $functionName): array => [$functionName],
            array_filter(
                $functionNames,
                static function (string $functionName): bool {
                    $reflection = new CoreReflectionFunction($functionName);

                    // Check only always enabled extensions
                    return in_array($reflection->getExtensionName(), self::EXTENSIONS, true);
                },
            ),
        );
    }

    /**
     * @dataProvider internalFunctionsProvider
     */
    public function testInternalFunctions(string $functionName): void
    {
        $stubbedReflection = $this->functionReflector->reflect($functionName);

        self::assertSame($functionName, $stubbedReflection->getName());
        self::assertTrue($stubbedReflection->isInternal());
        self::assertFalse($stubbedReflection->isUserDefined());

        $originalReflection = new CoreReflectionFunction($functionName);

        // Needs fixes in JetBrains/phpstorm-stubs
        if (
            in_array($functionName, [
                'strtr',
                'array_intersect',
                'array_intersect_key',
                'array_intersect_ukey',
                'array_intersect_assoc',
                'array_uintersect',
                'array_uintersect_assoc',
                'array_intersect_uassoc',
                'array_uintersect_uassoc',
                'array_diff',
                'array_diff_key',
                'array_diff_ukey',
                'array_diff_assoc',
                'array_udiff',
                'array_udiff_assoc',
                'array_diff_uassoc',
                'array_udiff_uassoc',
                'array_multisort',
                'extract',
                'setcookie',
                'setrawcookie',
                'stream_context_set_option',
            ], true)
        ) {
            return;
        }

        $stubbedReflectionParameters = $stubbedReflection->getParameters();
        foreach ($originalReflection->getParameters() as $parameterNo => $originalReflectionParameter) {
            $parameterName = sprintf('%s.%s', $functionName, $originalReflectionParameter->getName());

            $stubbedReflectionParameter = $stubbedReflectionParameters[$parameterNo];

            // Too much errors in JetBrains/phpstorm-stubs
            // self::assertSame($originalReflectionParameter->isOptional(), $stubbedReflectionParameter->isOptional(), $parameterName);

            self::assertSame($originalReflectionParameter->isPassedByReference(), $stubbedReflectionParameter->isPassedByReference(), $parameterName);
            self::assertSame($originalReflectionParameter->canBePassedByValue(), $stubbedReflectionParameter->canBePassedByValue(), $parameterName);

            // @ because isCallable() is deprecated
            self::assertSame(@$originalReflectionParameter->isCallable(), $stubbedReflectionParameter->isCallable(), $parameterName);

            self::assertSame($originalReflectionParameter->isVariadic(), $stubbedReflectionParameter->isVariadic(), $parameterName);

            // @ because getClass() is deprecated
            $class = @$originalReflectionParameter->getClass();
            if ($class) {
                // Needs fixes in JetBrains/phpstorm-stubs
                if ($parameterName !== 'assert.description') {
                    $stubbedClass = $stubbedReflectionParameter->getClass();
                    self::assertInstanceOf(ReflectionClass::class, $stubbedClass, $parameterName);
                    self::assertSame($class->getName(), $stubbedClass->getName(), $parameterName);
                }
            } else {
                self::assertNull($class, $parameterName);
            }
        }
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function internalConstantsProvider(): array
    {
        $provider = [];

        /** @var array<string, array<string, int|string|float|bool|array|resource|null>> $constants */
        $constants = get_defined_constants(true);

        foreach ($constants as $extensionName => $extensionConstants) {
            // Check only always enabled extensions
            if (! in_array($extensionName, self::EXTENSIONS, true)) {
                continue;
            }

            foreach ($extensionConstants as $constantName => $constantValue) {
                // Not supported because of resource as value
                if (in_array($constantName, ['STDIN', 'STDOUT', 'STDERR'], true)) {
                    continue;
                }

                $provider[] = [$constantName, $constantValue, $extensionName];
            }
        }

        return $provider;
    }

    /**
     * @dataProvider internalConstantsProvider
     */
    public function testInternalConstants(string $constantName, mixed $constantValue, string $extensionName): void
    {
        $constantReflection = $this->constantReflector->reflect($constantName);

        self::assertInstanceOf(ReflectionConstant::class, $constantReflection);
        self::assertSame($constantName, $constantReflection->getName());
        self::assertSame($constantName, $constantReflection->getShortName());

        self::assertNotNull($constantReflection->getNamespaceName());
        self::assertFalse($constantReflection->inNamespace());
        self::assertTrue($constantReflection->isInternal());
        self::assertFalse($constantReflection->isUserDefined());

        // Needs fixes in JetBrains/phpstorm-stubs
        if ($constantName !== 'PHP_MANDIR') {
            self::assertSame($extensionName, $constantReflection->getExtensionName());
        }

        // NAN cannot be compared
        if ($constantName === 'NAN') {
            return;
        }

        self::assertSame($constantValue, $constantReflection->getValue());
    }

    public function dataClassInNamespace(): array
    {
        return [
            ['http\\Client'],
            ['MongoDB\\Driver\\Manager'],
            ['Parle\\Stack'],
        ];
    }

    /**
     * @dataProvider dataClassInNamespace
     */
    public function testClassInNamespace(string $className): void
    {
        $classReflection = $this->classReflector->reflect($className);

        $this->assertSame($className, $classReflection->getName());
    }

    public function dataFunctionInNamespace(): array
    {
        return [
            ['Couchbase\\basicDecoderV1'],
            ['MongoDB\\BSON\\fromJSON'],
            ['Sodium\\add'],
        ];
    }

    /**
     * @dataProvider dataFunctionInNamespace
     */
    public function testFunctionInNamespace(string $functionName): void
    {
        $functionReflection = $this->functionReflector->reflect($functionName);

        $this->assertSame($functionName, $functionReflection->getName());
    }

    public function dataConstantInNamespace(): array
    {
        return [
            ['http\\Client\\Curl\\AUTH_ANY'],
            ['pcov\\all'],
            ['YAF\\ENVIRON'],
        ];
    }

    /**
     * @dataProvider dataConstantInNamespace
     */
    public function testConstantInNamespace(string $constantName): void
    {
        $constantReflection = $this->constantReflector->reflect($constantName);

        $this->assertSame($constantName, $constantReflection->getName());
    }

    public function testNoStubForUnknownClass(): void
    {
        self::assertNull($this->sourceStubber->generateClassStub('SomeClass'));
    }

    public function testNoStubForUnknownFunction(): void
    {
        self::assertNull($this->sourceStubber->generateFunctionStub('someFunction'));
    }

    public function testNoStubForUnknownConstant(): void
    {
        self::assertNull($this->sourceStubber->generateConstantStub('SOME_CONSTANT'));
    }

    public function dataCaseInsensitiveClass(): array
    {
        return [
            [
                'SoapFault',
                'SoapFault',
            ],
            [
                'SOAPFault',
                'SoapFault',
            ],
        ];
    }

    /**
     * @dataProvider dataCaseInsensitiveClass
     */
    public function testCaseInsensitiveClass(string $className, string $expectedClassName): void
    {
        $classReflection = $this->classReflector->reflect($className);

        $this->assertSame($expectedClassName, $classReflection->getName());
    }

    public function dataCaseInsensitiveFunction(): array
    {
        return [
            [
                'htmlspecialchars',
                'htmlspecialchars',
            ],
            [
                'htmlSpecialChars',
                'htmlspecialchars',
            ],
        ];
    }

    /**
     * @dataProvider dataCaseInsensitiveFunction
     */
    public function testCaseInsensitiveFunction(string $functionName, string $expectedFunctionName): void
    {
        $functionReflection = $this->functionReflector->reflect($functionName);

        $this->assertSame($expectedFunctionName, $functionReflection->getName());
    }

    public function dataCaseInsensitiveConstant(): array
    {
        return [
            [
                'true',
                'TRUE',
            ],
            [
                '__file__',
                '__FILE__',
            ],
            [
                'YaF_VeRsIoN',
                'YAF_VERSION',
            ],
        ];
    }

    /**
     * @dataProvider dataCaseInsensitiveConstant
     */
    public function testCaseInsensitiveConstant(string $constantName, string $expectedConstantName): void
    {
        $constantReflector = $this->constantReflector->reflect($constantName);

        $this->assertSame($expectedConstantName, $constantReflector->getName());
    }

    public function dataCaseSensitiveConstant(): array
    {
        return [
            ['date_atom'],
            ['PHP_version_ID'],
            ['FiLeInFo_NoNe'],
        ];
    }

    /**
     * @dataProvider dataCaseSensitiveConstant
     */
    public function testCaseSensitiveConstant(string $constantName): void
    {
        self::expectException(IdentifierNotFound::class);

        $this->constantReflector->reflect($constantName);
    }
}
