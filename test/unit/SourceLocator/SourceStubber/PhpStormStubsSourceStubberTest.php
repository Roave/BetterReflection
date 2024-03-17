<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\SourceStubber;

use CompileError;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeInterface;
use DOMNode;
use Error;
use Generator;
use ParseError;
use PDO;
use PDOException;
use PhpParser\Parser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionFunction as CoreReflectionFunction;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionNamedType as CoreReflectionNamedType;
use ReflectionParameter as CoreReflectionParameter;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubs\CachingVisitor;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\StubData;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use SplFileObject;
use Stringable;
use Throwable;
use Traversable;
use ZipArchive;

use function array_filter;
use function array_key_exists;
use function array_keys;
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

use const PHP_VERSION_ID;

#[CoversClass(PhpStormStubsSourceStubber::class)]
#[CoversClass(CachingVisitor::class)]
class PhpStormStubsSourceStubberTest extends TestCase
{
    private const EXTENSIONS = ['Core', 'standard', 'pcre', 'SPL'];

    private Parser $phpParser;

    private Locator $astLocator;

    private PhpStormStubsSourceStubber $sourceStubber;

    private PhpInternalSourceLocator $phpInternalSourceLocator;

    private Reflector $reflector;

    protected function setUp(): void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->phpParser                = $betterReflection->phpParser();
        $this->astLocator               = $betterReflection->astLocator();
        $this->sourceStubber            = new PhpStormStubsSourceStubber($this->phpParser, PHP_VERSION_ID);
        $this->phpInternalSourceLocator = new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber);
        $this->reflector                = new DefaultReflector($this->phpInternalSourceLocator);
    }

    /** @return list<array{0: string}> */
    public static function internalClassesProvider(): array
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

        self::assertEquals(
            $original->getConstants(),
            array_map(static fn (ReflectionClassConstant $classConstant) => $classConstant->getValue(), $stubbed->getConstants()),
        );
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

        self::assertSame($original->isPublic(), $stubbed->isPublic(), $methodName);
        self::assertSame($original->isPrivate(), $stubbed->isPrivate(), $methodName);
        self::assertSame($original->isProtected(), $stubbed->isProtected(), $methodName);
        self::assertSame($original->returnsReference(), $stubbed->returnsReference(), $methodName);
        self::assertSame($original->isStatic(), $stubbed->isStatic(), $methodName);
        self::assertSame($original->isFinal(), $stubbed->isFinal(), $methodName);

        // Needs fixes in JetBrains/phpstorm-stubs
        if (
            in_array($methodName, ['Closure#__invoke'], true)
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

        if (
            in_array($parameterName, [
                'ErrorException#__construct.filename',
                'ErrorException#__construct.line',
            ], true)
        ) {
            // These parameters have default values __FILE__ and __LINE__ and we cannot resolve them for stubs
            return;
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

                    // Check only always enabled extensions
                    return in_array($reflection->getExtensionName(), self::EXTENSIONS, true);
                },
            ),
        );
    }

    #[DataProvider('internalFunctionsProvider')]
    public function testInternalFunctions(string $functionName): void
    {
        $stubbedReflection = $this->reflector->reflectFunction($functionName);

        self::assertSame($functionName, $stubbedReflection->getName());
        self::assertTrue($stubbedReflection->isInternal());
        self::assertFalse($stubbedReflection->isUserDefined());

        $originalReflection = new CoreReflectionFunction($functionName);

        $stubbedReflectionParameters = $stubbedReflection->getParameters();

        if ($functionName === 'strrchr' && PHP_VERSION_ID >= 80300) {
            // New parameter in PHP 8.3.0
            return;
        }

        self::assertSame($originalReflection->getNumberOfParameters(), $stubbedReflection->getNumberOfParameters(), $functionName);

        foreach ($originalReflection->getParameters() as $parameterNo => $originalReflectionParameter) {
            $parameterName = sprintf('%s.%s', $functionName, $originalReflectionParameter->getName());

            $stubbedReflectionParameter = $stubbedReflectionParameters[$parameterNo];

            // Too much errors in JetBrains/phpstorm-stubs
            // self::assertSame($originalReflectionParameter->isOptional(), $stubbedReflectionParameter->isOptional(), $parameterName);

            self::assertSame($originalReflectionParameter->isPassedByReference(), $stubbedReflectionParameter->isPassedByReference(), $parameterName);
            if ($originalReflectionParameter->canBePassedByValue() && ! $originalReflectionParameter->isPassedByReference()) {
                // It's not possible to specify in stubs that parameter can be passed by value and passed by reference as well
                self::assertSame($originalReflectionParameter->canBePassedByValue(), $stubbedReflectionParameter->canBePassedByValue(), $parameterName);
            }

            self::assertSame($originalReflectionParameter->isVariadic(), $stubbedReflectionParameter->isVariadic(), $parameterName);
        }
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

    /** @return list<array{0: class-string}> */
    public static function dataClassInNamespace(): array
    {
        return [
            ['http\\Client'],
            ['MongoDB\\Driver\\Manager'],
            ['Parle\\Stack'],
        ];
    }

    #[DataProvider('dataClassInNamespace')]
    public function testClassInNamespace(string $className): void
    {
        $classReflection = $this->reflector->reflectClass($className);

        $this->assertSame($className, $classReflection->getName());
    }

    /** @return list<array{0: string}> */
    public static function dataFunctionInNamespace(): array
    {
        return [
            ['MongoDB\\BSON\\fromJSON'],
            ['Sodium\\add'],
        ];
    }

    #[DataProvider('dataFunctionInNamespace')]
    public function testFunctionInNamespace(string $functionName): void
    {
        $functionReflection = $this->reflector->reflectFunction($functionName);

        $this->assertSame($functionName, $functionReflection->getName());
    }

    /** @return list<array{0: string}> */
    public static function dataConstantInNamespace(): array
    {
        return [
            ['http\\Client\\Curl\\AUTH_ANY'],
            ['pcov\\all'],
            ['YAF\\ENVIRON'],
        ];
    }

    #[DataProvider('dataConstantInNamespace')]
    public function testConstantInNamespace(string $constantName): void
    {
        $constantReflection = $this->reflector->reflectConstant($constantName);

        $this->assertSame($constantName, $constantReflection->getName());
    }

    public function testNameResolverForClassInNamespace(): void
    {
        $classReflection     = $this->reflector->reflectClass('http\Client');
        $methodReflection    = $classReflection->getMethod('enqueue');
        $parameterReflection = $methodReflection->getParameter('request');

        self::assertSame('http\Client\Request', $parameterReflection->getType()->getName());
    }

    public function testStubForClassInNamespaceWithUses(): void
    {
        $stubData = $this->sourceStubber->generateClassStub('FFI');

        self::assertInstanceOf(StubData::class, $stubData);
        self::assertStringMatchesFormat(
            '%Ause FFI\CData;%Ause FFI\CType;%Ause FFI\ParserException;%A',
            $stubData->getStub(),
        );
        self::assertSame('FFI', $stubData->getExtensionName());
    }

    public function testStubForClassThatExists(): void
    {
        self::assertInstanceOf(StubData::class, $this->sourceStubber->generateClassStub('ReflectionClass'));
    }

    public function testNoStubForClassThatDoesNotExist(): void
    {
        /** @phpstan-var class-string $someClassName */
        $someClassName = 'SomeClass';
        self::assertNull($this->sourceStubber->generateClassStub($someClassName));
    }

    public function testStubForFunctionThatExists(): void
    {
        self::assertInstanceOf(StubData::class, $this->sourceStubber->generateFunctionStub('phpversion'));
    }

    public function testNoStubForFunctionThatDoesNotExist(): void
    {
        self::assertNull($this->sourceStubber->generateFunctionStub('someFunction'));
    }

    public function testStubForConstantThatExists(): void
    {
        $stubData = $this->sourceStubber->generateConstantStub('PHP_VERSION_ID');

        self::assertInstanceOf(StubData::class, $stubData);
        self::assertStringMatchesFormat(
            "%Adefine('PHP_VERSION_ID',%w%d);",
            $stubData->getStub(),
        );
        self::assertSame('Core', $stubData->getExtensionName());
    }

    public function testStubForConstantThatIsDeprecated(): void
    {
        $stubData = $this->sourceStubber->generateConstantStub('MT_RAND_PHP');

        self::assertStringContainsString(
            'define("MT_RAND_PHP", 1);',
            $stubData->getStub(),
        );

        if (PHP_VERSION_ID >= 80300) {
            self::assertStringContainsString(
                '@deprecated 8.3',
                $stubData->getStub(),
            );
        } else {
            self::assertStringNotContainsString(
                '@deprecated 8.3',
                $stubData->getStub(),
            );
        }

        self::assertSame('standard', $stubData->getExtensionName());
    }

    public function testNoStubForConstantThatDoesNotExist(): void
    {
        self::assertNull($this->sourceStubber->generateConstantStub('SOME_CONSTANT'));
    }

    public function testStubForConstantDeclaredByDefine(): void
    {
        $stub = $this->sourceStubber->generateConstantStub('PHP_VERSION_ID');

        self::assertInstanceOf(StubData::class, $stub);
        self::assertStringEndsWith(";\n", $stub->getStub());
    }

    public function testStubForConstantDeclaredByConst(): void
    {
        $stub = $this->sourceStubber->generateConstantStub('ast\AST_ARG_LIST');

        self::assertInstanceOf(StubData::class, $stub);
    }

    /** @return list<array{0: class-string, 1: class-string}> */
    public static function dataCaseInsensitiveClass(): array
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

    #[DataProvider('dataCaseInsensitiveClass')]
    public function testCaseInsensitiveClass(string $className, string $expectedClassName): void
    {
        $classReflection = $this->reflector->reflectClass($className);

        $this->assertSame($expectedClassName, $classReflection->getName());
    }

    /** @return list<array{0: string, 1: string}> */
    public static function dataCaseInsensitiveFunction(): array
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

    #[DataProvider('dataCaseInsensitiveFunction')]
    public function testCaseInsensitiveFunction(string $functionName, string $expectedFunctionName): void
    {
        $functionReflection = $this->reflector->reflectFunction($functionName);

        $this->assertSame($expectedFunctionName, $functionReflection->getName());
    }

    /** @return list<array{0: string, 1: string}> */
    public static function dataCaseInsensitiveConstant(): array
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

    #[DataProvider('dataCaseInsensitiveConstant')]
    public function testCaseInsensitiveConstant(string $constantName, string $expectedConstantName): void
    {
        $reflector = $this->reflector->reflectConstant($constantName);

        $this->assertSame($expectedConstantName, $reflector->getName());
    }

    /** @return list<array{0: string}> */
    public static function dataCaseSensitiveConstantSearchedByWrongCase(): array
    {
        return [
            ['date_atom'],
            ['PHP_version_ID'],
            ['FiLeInFo_NoNe'],
        ];
    }

    #[DataProvider('dataCaseSensitiveConstantSearchedByWrongCase')]
    public function testCaseSensitiveConstantSearchedByWrongCase(string $constantName): void
    {
        $this->expectException(IdentifierNotFound::class);

        $this->reflector->reflectConstant($constantName);
    }

    /** @return list<array{0: string}> */
    public static function dataCaseSensitiveConstantSearchedByRightCase(): array
    {
        return [
            ['DATE_ATOM'],
            ['PHP_VERSION_ID'],
            ['FILEINFO_NONE'],
        ];
    }

    #[DataProvider('dataCaseSensitiveConstantSearchedByRightCase')]
    public function testCaseSensitiveConstantSearchedByRightCase(string $constantName): void
    {
        self::assertInstanceOf(ReflectionConstant::class, $this->reflector->reflectConstant($constantName));
    }

    /**
     * The second search should use optimization, see code coverage.
     */
    public function testCaseSensitiveConstantSearchOptimization(): void
    {
        self::assertNull($this->sourceStubber->generateConstantStub('date_atom'));
        self::assertNull($this->sourceStubber->generateConstantStub('date_atom'));
    }

    #[RunInSeparateProcess]
    public function testUpdateConstantValue(): void
    {
        require __DIR__ . '/../../Fixture/FakeConstants.php';

        $sourceStubber = new PhpStormStubsSourceStubber(BetterReflectionSingleton::instance()->phpParser());

        $stubberReflection = new CoreReflectionClass($sourceStubber);

        $stubDirectoryReflection = $stubberReflection->getProperty('stubsDirectory');
        $stubDirectoryReflection->setAccessible(true);
        $stubDirectoryReflection->setValue($sourceStubber, __DIR__ . '/../../Fixture');

        $classMapReflection = $stubberReflection->getProperty('classMap');
        $classMapReflection->setAccessible(true);
        $classMapValue                                                     = $classMapReflection->getValue();
        $classMapValue['roave\betterreflectiontest\fixture\fakeconstants'] = 'fakeconstants/FakeConstantsStub.php';
        $classMapReflection->setValue($classMapReflection, $classMapValue);

        $constantMapReflection = $stubberReflection->getProperty('constantMap');
        $constantMapReflection->setAccessible(true);
        $constantMapValue                                                      = $constantMapReflection->getValue();
        $constantMapValue['define_constant']                                   = 'fakeconstants/FakeConstantsStub.php';
        $constantMapValue['roave\betterreflectiontest\fixture\const_constant'] = 'fakeconstants/FakeConstantsStub.php';
        $constantMapReflection->setValue($constantMapReflection, $constantMapValue);

        $classConstantStub = $sourceStubber->generateClassStub('Roave\BetterReflectionTest\Fixture\FakeConstants');
        self::assertNotNull($classConstantStub);
        self::assertStringContainsString("const CLASS_CONSTANT = 'actualValue';", $classConstantStub->getStub());

        $defineConstantStub = $sourceStubber->generateConstantStub('DEFINE_CONSTANT');
        self::assertNotNull($defineConstantStub);
        self::assertStringContainsString("define('DEFINE_CONSTANT', 'actualValue');", $defineConstantStub->getStub());

        $constConstantStub = $sourceStubber->generateConstantStub('Roave\BetterReflectionTest\Fixture\CONST_CONSTANT');
        self::assertNotNull($constConstantStub);
        self::assertStringContainsString("const CONST_CONSTANT = 'actualValue';", $constConstantStub->getStub());
    }

    /** @return list<array{0: class-string|string, 1: int, 2: bool}> */
    public static function dataClassInPhpVersion(): array
    {
        return [
            [CoreReflectionNamedType::class, 70000, false],
            [CoreReflectionNamedType::class, 70100, true],
            [CoreReflectionNamedType::class, 70200, true],
            [CompileError::class, 70300, true],
            [CompileError::class, 70000, false],
            [Stringable::class, 80000, true],
            [Stringable::class, 70400, false],
            [Stringable::class, 70399, false],
            ['DOMNameList', 79999, true],
            ['DOMNameList', 80000, false],
        ];
    }

    #[DataProvider('dataClassInPhpVersion')]
    public function testClassInPhpVersion(string $className, int $phpVersion, bool $isSupported): void
    {
        $sourceStubber = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);

        $stub = $sourceStubber->generateClassStub($className);

        if ($isSupported) {
            self::assertNotNull($stub, $className);
        } else {
            self::assertNull($stub, $className);
        }
    }

    /** @return list<array{0: class-string, 1: string, 2: int, 3: bool}> */
    public static function dataClassConstantInPhpVersion(): array
    {
        return [
            [DateTimeInterface::class, 'ATOM', 70200, true],
            [DateTimeInterface::class, 'ATOM', 70100, false],
            [DateTime::class, 'ATOM', 70100, true],
            [DateTime::class, 'ATOM', 70199, true],
            [DateTime::class, 'ATOM', 70200, false],
            [PDO::class, 'FETCH_DEFAULT', 80007, true],
            [PDO::class, 'FETCH_DEFAULT', 80006, false],
            [ZipArchive::class, 'LIBZIP_VERSION', 70403, true],
            [ZipArchive::class, 'LIBZIP_VERSION', 70402, false],
        ];
    }

    #[DataProvider('dataClassConstantInPhpVersion')]
    public function testClassConstantInPhpVersion(string $className, string $constantName, int $phpVersion, bool $isSupported): void
    {
        $sourceStubber            = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $phpInternalSourceLocator = new PhpInternalSourceLocator($this->astLocator, $sourceStubber);
        $reflector                = new DefaultReflector($phpInternalSourceLocator);

        $constants = $reflector->reflectClass($className)->getImmediateConstants();

        self::assertSame($isSupported, array_key_exists($constantName, $constants));
    }

    /** @return list<array{0: class-string, 1: non-empty-string, 2: int, 3: bool, 4?: string|null, 5?: string}> */
    public static function dataMethodInPhpVersion(): array
    {
        return [
            [CoreReflectionProperty::class, 'hasType', 70300, false],
            [CoreReflectionProperty::class, 'hasType', 70400, true],
            [CoreReflectionProperty::class, 'hasType', 80100, true, null, 'bool'],
            [CoreReflectionProperty::class, 'getType', 70300, false],
            [CoreReflectionProperty::class, 'getType', 70400, true],
            [CoreReflectionProperty::class, 'getType', 80000, true],
            [CoreReflectionProperty::class, 'getType', 80100, true, null, 'ReflectionType|null'],
            [CoreReflectionClass::class, 'export', 70400, true],
            [CoreReflectionClass::class, 'export', 80000, false],
            [DatePeriod::class, 'getRecurrences', 70216, false],
            [DatePeriod::class, 'getRecurrences', 70217, true],
            [DatePeriod::class, 'getRecurrences', 80100, true, null, 'int|null'],
            [DateTimeInterface::class, 'getOffset', 79999, true],
            [DateTimeInterface::class, 'getOffset', 80000, true],
            [DateTimeInterface::class, 'getOffset', 80100, true, null, 'int'],
            [SplFileObject::class, 'fgetss', 79999, true],
            [SplFileObject::class, 'fgetss', 80000, false],
            [Throwable::class, 'getLine', 80000, true, 'int'],
            [Throwable::class, 'getLine', 80100, true, 'int'],
        ];
    }

    /** @param non-empty-string $methodName */
    #[DataProvider('dataMethodInPhpVersion')]
    public function testMethodInPhpVersion(
        string $className,
        string $methodName,
        int $phpVersion,
        bool $isSupported,
        string|null $returnType = null,
        string|null $tentativeReturnType = null,
    ): void {
        $sourceStubber = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $sourceLocator = new AggregateSourceLocator([
            // We need to hack Stringable to make the test work
            new StringSourceLocator('<?php interface Stringable {}', $this->astLocator),
            new PhpInternalSourceLocator($this->astLocator, $sourceStubber),
        ]);
        $reflector     = new DefaultReflector($sourceLocator);

        $class = $reflector->reflectClass($className);

        $fullMethodName = sprintf('%s#%s', $className, $methodName);

        if ($isSupported) {
            self::assertTrue($class->hasMethod($methodName), $fullMethodName);

            $method = $class->getMethod($methodName);

            self::assertSame($returnType, $method->getReturnType()?->__toString());
            self::assertSame($tentativeReturnType, $method->getTentativeReturnType()?->__toString());
        } else {
            self::assertFalse($class->hasMethod($methodName), $fullMethodName);
        }
    }

    /** @return list<array{0: class-string, 1: non-empty-string, 2: non-empty-string, 3: int, 4: bool, 5?: string|null, 6?: bool}> */
    public static function dataMethodParameterInPhpVersion(): array
    {
        return [
            ['mysqli_stmt', 'execute', 'params', 80099, false],
            ['mysqli_stmt', 'execute', 'params', 80100, true, 'array|null', true],
            ['PDOStatement', 'fetchAll', 'fetch_argument', 50299, false],
            ['PDOStatement', 'fetchAll', 'fetch_argument', 50300, true, null, true],
            ['PDOStatement', 'fetchAll', 'fetch_argument', 70499, true, null, true],
            ['PDOStatement', 'fetchAll', 'fetch_argument', 70500, false],
        ];
    }

    /**
     * @param non-empty-string $methodName
     * @param non-empty-string $parameterName
     */
    #[DataProvider('dataMethodParameterInPhpVersion')]
    public function testMethodParameterInPhpVersion(
        string $className,
        string $methodName,
        string $parameterName,
        int|null $phpVersion,
        bool $isSupported,
        string|null $type = null,
        bool|null $allowsNull = null,
    ): void {
        $sourceStubber            = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $phpInternalSourceLocator = new PhpInternalSourceLocator($this->astLocator, $sourceStubber);
        $reflector                = new DefaultReflector($phpInternalSourceLocator);

        $class     = $reflector->reflectClass($className);
        $method    = $class->getMethod($methodName);
        $parameter = $method->getParameter($parameterName);

        $fullParameterName = sprintf('%s#%s.$%s', $className, $methodName, $parameterName);

        if ($isSupported) {
            self::assertInstanceOf(ReflectionParameter::class, $parameter, $fullParameterName);
            self::assertSame($type, $parameter->getType()?->__toString(), $fullParameterName);
            self::assertSame($allowsNull, $parameter->allowsNull(), $fullParameterName);
        } else {
            self::assertNull($parameter, $fullParameterName);
        }
    }

    /** @return list<array{0: class-string, 1: non-empty-string, 2: int, 3: bool, 4?: string}> */
    public static function dataPropertyInPhpVersion(): array
    {
        return [
            [DateInterval::class, 'f', 70000, false],
            [DateInterval::class, 'f', 70099, false],
            [DateInterval::class, 'f', 70100, true],
            [PDOException::class, 'errorInfo', 80099, true],
            [PDOException::class, 'errorInfo', 80100, true, 'array|null'],
            [DOMNode::class, 'nodeType', 80099, true],
            [DOMNode::class, 'nodeType', 80100, true, 'int'],
            [DOMNode::class, 'parentNode', 80099, true],
            [DOMNode::class, 'parentNode', 80100, true, 'DOMNode|null'],
        ];
    }

    /** @param non-empty-string $propertyName */
    #[DataProvider('dataPropertyInPhpVersion')]
    public function testPropertyInPhpVersion(string $className, string $propertyName, int $phpVersion, bool $isSupported, string|null $type = null): void
    {
        $sourceStubber            = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $phpInternalSourceLocator = new PhpInternalSourceLocator($this->astLocator, $sourceStubber);
        $reflector                = new DefaultReflector($phpInternalSourceLocator);

        $class    = $reflector->reflectClass($className);
        $property = $class->getProperty($propertyName);

        $fullPropertyName = sprintf('%s::$%s', $className, $propertyName);

        if ($isSupported) {
            self::assertInstanceOf(ReflectionProperty::class, $property, $fullPropertyName);
            self::assertSame($type, $property->getType()?->__toString(), $fullPropertyName);
        } else {
            self::assertNull($property, $fullPropertyName);
        }
    }

    /** @return list<array{0: string, 1: int, 2: bool, 3?: string}> */
    public static function dataFunctionInPhpVersion(): array
    {
        return [
            ['password_algos', 70300, false],
            ['password_algos', 70400, true, 'array'],
            ['array_key_first', 70200, false],
            ['array_key_first', 70300, true, 'string|int|null'],
            ['str_starts_with', 70400, false],
            ['str_starts_with', 80000, true, 'bool'],
            ['mysql_query', 50400, true],
            ['mysql_query', 70000, false],
            ['hash_hkdf', 70101, false],
            ['hash_hkdf', 70102, true, 'string|false'],
            ['hash_hkdf', 80000, true, 'string'],
            ['read_exif_data', 79999, true],
            ['read_exif_data', 80000, false],
            ['spl_autoload_functions', 79999, true, 'array|false'],
            ['spl_autoload_functions', 80000, true, 'array'],
            ['dom_import_simplexml', 70000, true, 'DOMElement|null'],
            ['dom_import_simplexml', 80000, true, 'DOMElement'],
            // Not core functions
            ['newrelic_add_custom_parameter', 40000, true, 'bool'],
        ];
    }

    #[DataProvider('dataFunctionInPhpVersion')]
    public function testFunctionInPhpVersion(string $functionName, int $phpVersion, bool $isSupported, string|null $returnType = null): void
    {
        $sourceStubber            = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $phpInternalSourceLocator = new PhpInternalSourceLocator($this->astLocator, $sourceStubber);
        $reflector                = new DefaultReflector($phpInternalSourceLocator);

        if ($isSupported) {
            $function = $reflector->reflectFunction($functionName);

            self::assertInstanceOf(ReflectionFunction::class, $function, $functionName);
            self::assertSame($returnType, $function->getReturnType()?->__toString());
        } else {
            $this->expectException(IdentifierNotFound::class);
            $this->expectExceptionMessage(sprintf('Function "%s" could not be found in the located source', $functionName));
            $reflector->reflectFunction($functionName);
        }
    }

    /** @return list<array{0: string, 1: non-empty-string, 2: int, 3: bool, 4?: string|null, 5?: bool}> */
    public static function dataFunctionParameterInPhpVersion(): array
    {
        return [
            ['bcscale', 'scale', 70200, true, 'int', false],
            ['bcscale', 'scale', 70299, true, 'int', false],
            ['bcscale', 'scale', 70300, true, 'int|null', true],
            ['bcscale', 'scale', 80000, true, 'int|null', true],
            ['easter_date', 'mode', 79999, false],
            ['easter_date', 'mode', 80000, true, 'int', false],
            ['curl_version', 'age', 50200, false],
            ['curl_version', 'age', 50299, false],
            ['curl_version', 'age', 50300, true, null, true],
            ['curl_version', 'age', 70400, true, null, true],
            ['curl_version', 'age', 70499, true, null, true],
            ['curl_version', 'age', 70500, false],
        ];
    }

    /** @param non-empty-string $parameterName */
    #[DataProvider('dataFunctionParameterInPhpVersion')]
    public function testFunctionParameterInPhpVersion(
        string $functionName,
        string $parameterName,
        int|null $phpVersion,
        bool $isSupported,
        string|null $type = null,
        bool|null $allowsNull = null,
    ): void {
        $sourceStubber            = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $phpInternalSourceLocator = new PhpInternalSourceLocator($this->astLocator, $sourceStubber);
        $reflector                = new DefaultReflector($phpInternalSourceLocator);

        $function  = $reflector->reflectFunction($functionName);
        $parameter = $function->getParameter($parameterName);

        $fullParameterName = sprintf('%s::$%s', $functionName, $parameterName);

        if ($isSupported) {
            self::assertInstanceOf(ReflectionParameter::class, $parameter, $fullParameterName);
            self::assertSame($type, $parameter->getType()?->__toString(), $fullParameterName);
            self::assertSame($allowsNull, $parameter->allowsNull(), $fullParameterName);
        } else {
            self::assertNull($parameter, $fullParameterName);
        }
    }

    /** @return list<array{0: string, 1: int, 2: bool}> */
    public static function dataConstantInPhpVersion(): array
    {
        return [
            ['PHP_OS_FAMILY', 70200, true],
            ['PHP_OS_FAMILY', 70100, false],
            ['INPUT_SESSION', 70400, true],
            ['INPUT_SESSION', 79999, true],
            ['INPUT_SESSION', 80000, false],
            ['CURL_VERSION_ALTSVC', 70306, true],
            ['CURL_VERSION_ALTSVC', 70305, false],
            // Not core constants
            ['RADIUS_DISCONNECT_ACK', 10000, true],
            ['RADIUS_DISCONNECT_ACK', 80000, true],
        ];
    }

    #[DataProvider('dataConstantInPhpVersion')]
    public function testConstantInPhpVersion(string $constantName, int $phpVersion, bool $isSupported): void
    {
        $sourceStubber = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);

        $stub = $sourceStubber->generateConstantStub($constantName);

        if ($isSupported) {
            self::assertNotNull($stub, $constantName);
        } else {
            self::assertNull($stub, $constantName);
        }
    }

    /** @return list<array{0: class-string, 1: int, 2: bool}> */
    public static function dataClassIsDeprecatedInPhpVersion(): array
    {
        return [
            ['Mongo', 70000, true],
            ['Mongo', 80000, true],
            [DateTimeInterface::class, 70400, false],
            [DateTimeInterface::class, 80000, false],
        ];
    }

    #[DataProvider('dataClassIsDeprecatedInPhpVersion')]
    public function testClassIsDeprecatedInPhpVersion(string $className, int $phpVersion, bool $isDeprecated): void
    {
        $sourceStubber = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $reflector     = new DefaultReflector(new PhpInternalSourceLocator($this->astLocator, $sourceStubber));

        $classReflection = $reflector->reflectClass($className);

        self::assertSame($isDeprecated, $classReflection->isDeprecated());
    }

    /** @return list<array{0: class-string, 1: non-empty-string, 2: int, 3: bool}> */
    public static function dataClassConstantIsDeprecatedInPhpVersion(): array
    {
        return [
            [PDO::class, 'PARAM_BOOL', 70000, false],
            [PDO::class, 'PARAM_BOOL', 80000, false],
            [PDO::class, 'PGSQL_ASSOC', 70000, true],
            [PDO::class, 'PGSQL_ASSOC', 80000, true],
        ];
    }

    /** @param non-empty-string $constantName */
    #[DataProvider('dataClassConstantIsDeprecatedInPhpVersion')]
    public function testClassConstantIsDeprecatedInPhpVersion(string $className, string $constantName, int $phpVersion, bool $isDeprecated): void
    {
        $sourceStubber = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $reflector     = new DefaultReflector(new PhpInternalSourceLocator($this->astLocator, $sourceStubber));

        $classReflection    = $reflector->reflectClass($className);
        $constantReflection = $classReflection->getConstant($constantName);

        self::assertSame($isDeprecated, $constantReflection->isDeprecated());
    }

    /** @return list<array{0: class-string, 1: non-empty-string, 2: int, 3: bool}> */
    public static function dataMethodIsDeprecatedInPhpVersion(): array
    {
        return [
            [CoreReflectionClass::class, 'getName', 70400, false],
            [CoreReflectionClass::class, 'export', 70300, false],
            [CoreReflectionClass::class, 'export', 70399, false],
            [CoreReflectionClass::class, 'export', 70400, true],
        ];
    }

    /** @param non-empty-string $methodName */
    #[DataProvider('dataMethodIsDeprecatedInPhpVersion')]
    public function testMethodIsDeprecatedInPhpVersion(string $className, string $methodName, int $phpVersion, bool $isDeprecated): void
    {
        $sourceStubber = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $sourceLocator = new AggregateSourceLocator([
            // We need to hack Stringable to make the test work
            new StringSourceLocator('<?php interface Stringable {}', $this->astLocator),
            new PhpInternalSourceLocator($this->astLocator, $sourceStubber),
        ]);
        $reflector     = new DefaultReflector($sourceLocator);

        $classReflection  = $reflector->reflectClass($className);
        $methodReflection = $classReflection->getMethod($methodName);

        self::assertSame($isDeprecated, $methodReflection->isDeprecated());
    }

    /** @return list<array{0: string, 1: non-empty-string, 2: int, 3: bool}> */
    public static function dataPropertyIsDeprecatedInPhpVersion(): array
    {
        return [
            ['DateInterval', 'y', 70000, false],
            ['DateInterval', 'y', 80000, false],
            ['DOMDocument', 'actualEncoding', 70000, true],
            ['DOMDocument', 'actualEncoding', 80000, true],
        ];
    }

    /** @param non-empty-string $propertyName */
    #[DataProvider('dataPropertyIsDeprecatedInPhpVersion')]
    public function testPropertyIsDeprecatedInPhpVersion(string $className, string $propertyName, int $phpVersion, bool $isDeprecated): void
    {
        $sourceStubber = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $reflector     = new DefaultReflector(new PhpInternalSourceLocator($this->astLocator, $sourceStubber));

        $classReflection    = $reflector->reflectClass($className);
        $propertyReflection = $classReflection->getProperty($propertyName);

        self::assertSame($isDeprecated, $propertyReflection->isDeprecated());
    }

    /** @return list<array{0: string, 1: int, 2: bool}> */
    public static function dataFunctionIsDeprecatedInPhpVersion(): array
    {
        return [
            ['strpos', 70000, false],
            ['strpos', 80000, false],
            ['create_function', 70100, false],
            ['create_function', 70199, false],
            ['create_function', 70200, true],
            ['date_sunrise', 80000, false],
            ['date_sunrise', 80099, false],
            ['date_sunrise', 80100, true],
        ];
    }

    #[DataProvider('dataFunctionIsDeprecatedInPhpVersion')]
    public function testFunctionIsDeprecatedInPhpVersion(string $functionName, int $phpVersion, bool $isDeprecated): void
    {
        $sourceStubber = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $reflector     = new DefaultReflector(new PhpInternalSourceLocator($this->astLocator, $sourceStubber));

        $functionReflection = $reflector->reflectFunction($functionName);

        self::assertSame($isDeprecated, $functionReflection->isDeprecated());
    }

    public function testModifiedStubForTraversableClass(): void
    {
        $classReflection = $this->reflector->reflectClass(Traversable::class);

        self::assertInstanceOf(ReflectionClass::class, $classReflection);
    }

    public function testModifiedStubForGeneratorClass(): void
    {
        $classReflection = $this->reflector->reflectClass(Generator::class);

        self::assertInstanceOf(ReflectionClass::class, $classReflection);
        self::assertTrue($classReflection->hasMethod('throw'));
    }

    /** @return list<array{0: class-string, 1: list<class-string>, 2: int}> */
    public static function dataImmediateInterfaces(): array
    {
        return [
            [
                'PDOStatement',
                ['Traversable'],
                70400,
            ],
            [
                'PDOStatement',
                ['IteratorAggregate'],
                80000,
            ],
            [
                'DatePeriod',
                ['Traversable'],
                70400,
            ],
            [
                'DatePeriod',
                ['IteratorAggregate'],
                80000,
            ],
            [
                'SplFixedArray',
                ['Iterator', 'ArrayAccess', 'Countable'],
                70400,
            ],
            [
                'SplFixedArray',
                ['ArrayAccess', 'Countable', 'IteratorAggregate'],
                80000,
            ],
            [
                'SplFixedArray',
                ['ArrayAccess', 'Countable', 'IteratorAggregate', 'JsonSerializable'],
                80100,
            ],
            [
                'SplFixedArray',
                ['Iterator', 'ArrayAccess', 'Countable'],
                70400,
            ],
            [
                'SimpleXMLElement',
                ['Traversable', 'ArrayAccess', 'Countable', 'Iterator'],
                70400,
            ],
            [
                'SimpleXMLElement',
                ['Traversable', 'ArrayAccess', 'Countable', 'Iterator', 'Stringable', 'RecursiveIterator'],
                80000,
            ],
            [
                'DOMDocument',
                [],
                70400,
            ],
            [
                'DOMDocument',
                ['DOMParentNode'],
                80000,
            ],
        ];
    }

    /** @param string[] $interfaceNames */
    #[DataProvider('dataImmediateInterfaces')]
    public function testImmediateInterfaces(
        string $className,
        array $interfaceNames,
        int $phpVersion,
    ): void {
        $sourceStubber            = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $phpInternalSourceLocator = new PhpInternalSourceLocator($this->astLocator, $sourceStubber);
        $reflector                = new DefaultReflector($phpInternalSourceLocator);
        $class                    = $reflector->reflectClass($className);

        self::assertSame($interfaceNames, array_keys($class->getImmediateInterfaces()));
    }

    /** @return list<array{0: class-string, 1: class-string, 2: int}> */
    public static function dataSubclass(): array
    {
        return [
            [
                ParseError::class,
                CompileError::class,
                70300,
            ],
            [
                ParseError::class,
                CompileError::class,
                70400,
            ],
            [
                ParseError::class,
                Error::class,
                70200,
            ],
        ];
    }

    #[DataProvider('dataSubclass')]
    public function testSubclass(
        string $className,
        string $subclassName,
        int $phpVersion,
    ): void {
        $sourceStubber            = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $phpInternalSourceLocator = new PhpInternalSourceLocator($this->astLocator, $sourceStubber);
        $reflector                = new DefaultReflector($phpInternalSourceLocator);
        $class                    = $reflector->reflectClass($className);

        self::assertTrue($class->isSubclassOf($subclassName));
    }

    /** @return list<array{0: string}> */
    public static function dataIterable(): array
    {
        return [
            ['iterable'],
            ['Iterable'],
            ['ItErAbLe'],
        ];
    }

    #[DataProvider('dataIterable')]
    public function testIterableInterfaceDoesNotExist(string $className): void
    {
        $stub = $this->sourceStubber->generateClassStub($className);

        self::assertNull($stub);
    }
}
