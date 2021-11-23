<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\SourceStubber;

use CompileError;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeInterface;
use DOMNode;
use Generator;
use PDO;
use PDOException;
use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionFunction as CoreReflectionFunction;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionNamedType as CoreReflectionNamedType;
use ReflectionParameter as CoreReflectionParameter;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\StubData;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflection\Util\FileHelper;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use SplFileObject;
use Stringable;
use Traversable;
use ZipArchive;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function get_declared_classes;
use function get_declared_interfaces;
use function get_declared_traits;
use function get_defined_constants;
use function get_defined_functions;
use function in_array;
use function realpath;
use function sort;
use function sprintf;

use const PHP_VERSION_ID;

/**
 * @covers \Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber
 * @covers \Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubs\CachingVisitor
 */
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
        $this->sourceStubber            = new PhpStormStubsSourceStubber($this->phpParser, 80100);
        $this->phpInternalSourceLocator = new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber);
        $this->reflector                = new DefaultReflector($this->phpInternalSourceLocator);
    }

    /**
     * @return list<list<string>>
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

        // Needs fix in JetBrains/phpstorm-stubs
        if ($original->getName() === 'SplFixedArray') {
            return;
        }

        $this->assertSameInterfaces($original, $stubbed);

        foreach ($original->getMethods() as $method) {
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

        self::assertSame($original->isPublic(), $stubbed->isPublic(), $methodName);
        self::assertSame($original->isPrivate(), $stubbed->isPrivate(), $methodName);
        self::assertSame($original->isProtected(), $stubbed->isProtected(), $methodName);
        self::assertSame($original->returnsReference(), $stubbed->returnsReference(), $methodName);
        self::assertSame($original->isStatic(), $stubbed->isStatic(), $methodName);

        // Needs fix in JetBrains/phpstorm-stubs
        if (! (PHP_VERSION_ID >= 80100 && in_array($methodName, ['Error#__clone', 'Exception#__clone'], true))) {
            self::assertSame($original->isFinal(), $stubbed->isFinal(), $methodName);
        }

        // Needs fixes in JetBrains/phpstorm-stubs
        if (
            in_array($methodName, [
                'Closure#__invoke',
                'Directory#read',
                'Directory#rewind',
                'Directory#close',
                'SplFileObject#fputcsv',
                'SplTempFileObject#fputcsv',
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

        if (
            in_array($parameterName, [
                'ErrorException#__construct.filename',
                'ErrorException#__construct.line',
            ], true)
        ) {
            // These parameters have default values __FILE__ and __LINE__ and we cannot resolve them for stubs
            return;
        }

        // @ because isArray() is deprecated
        self::assertSame(@$original->isArray(), $stubbed->isArray(), $parameterName);

        // @ because isCallable() is deprecated
        self::assertSame(@$original->isCallable(), $stubbed->isCallable(), $parameterName);

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

            self::assertInstanceOf(ReflectionClass::class, $stubbedClass, $parameterName);
            self::assertSame($class->getName(), $stubbedClass->getName(), $parameterName);
        } else {
            self::assertNull($stubbed->getClass(), $parameterName);
        }
    }

    /**
     * @return list<list<string>>
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
        $stubbedReflection = $this->reflector->reflectFunction($functionName);

        self::assertSame($functionName, $stubbedReflection->getName());
        self::assertTrue($stubbedReflection->isInternal());
        self::assertFalse($stubbedReflection->isUserDefined());

        $originalReflection = new CoreReflectionFunction($functionName);

        // There are more versions in PHP
        if (
            in_array($functionName, [
                'fputcsv',
                'setcookie',
                'setrawcookie',
                'stream_context_set_option',
                'strtr',
            ], true)
        ) {
            return;
        }

        $stubbedReflectionParameters = $stubbedReflection->getParameters();

        self::assertSame($originalReflection->getNumberOfParameters(), $stubbedReflection->getNumberOfParameters());

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

            // @ because isCallable() is deprecated
            self::assertSame(@$originalReflectionParameter->isCallable(), $stubbedReflectionParameter->isCallable(), $parameterName);

            self::assertSame($originalReflectionParameter->isVariadic(), $stubbedReflectionParameter->isVariadic(), $parameterName);

            // @ because getClass() is deprecated
            $class = @$originalReflectionParameter->getClass();
            if ($class) {
                $stubbedClass = $stubbedReflectionParameter->getClass();
                self::assertInstanceOf(ReflectionClass::class, $stubbedClass, $parameterName);
                self::assertSame($class->getName(), $stubbedClass->getName(), $parameterName);
            } else {
                self::assertNull($class, $parameterName);
            }
        }
    }

    /**
     * @return list<list<mixed>>
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
        $constantReflection = $this->reflector->reflectConstant($constantName);

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
        $classReflection = $this->reflector->reflectClass($className);

        $this->assertSame($className, $classReflection->getName());
    }

    public function dataFunctionInNamespace(): array
    {
        return [
            ['MongoDB\\BSON\\fromJSON'],
            ['Sodium\\add'],
        ];
    }

    /**
     * @dataProvider dataFunctionInNamespace
     */
    public function testFunctionInNamespace(string $functionName): void
    {
        $functionReflection = $this->reflector->reflectFunction($functionName);

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

    public function testStubForClassThatExists(): void
    {
        self::assertInstanceOf(StubData::class, $this->sourceStubber->generateClassStub('ReflectionClass'));
    }

    public function testNoStubForClassThatDoesNotExist(): void
    {
        self::assertNull($this->sourceStubber->generateClassStub('SomeClass'));
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
        $classReflection = $this->reflector->reflectClass($className);

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
        $functionReflection = $this->reflector->reflectFunction($functionName);

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
        $reflector = $this->reflector->reflectConstant($constantName);

        $this->assertSame($expectedConstantName, $reflector->getName());
    }

    public function dataCaseSensitiveConstantSearchedByWrongCase(): array
    {
        return [
            ['date_atom'],
            ['PHP_version_ID'],
            ['FiLeInFo_NoNe'],
        ];
    }

    /**
     * @dataProvider dataCaseSensitiveConstantSearchedByWrongCase
     */
    public function testCaseSensitiveConstantSearchedByWrongCase(string $constantName): void
    {
        self::expectException(IdentifierNotFound::class);

        $this->reflector->reflectConstant($constantName);
    }

    public function dataCaseSensitiveConstantSearchedByRightCase(): array
    {
        return [
            ['DATE_ATOM'],
            ['PHP_VERSION_ID'],
            ['FILEINFO_NONE'],
        ];
    }

    /**
     * @dataProvider dataCaseSensitiveConstantSearchedByRightCase
     */
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

    /**
     * @runInSeparateProcess
     */
    public function testUpdateConstantValue(): void
    {
        require __DIR__ . '/../../Fixture/FakeConstants.php';

        $sourceStubber = new PhpStormStubsSourceStubber(BetterReflectionSingleton::instance()->phpParser());

        $stubberReflection = new CoreReflectionClass($sourceStubber);

        $stubDirectoryReflection = $stubberReflection->getProperty('stubsDirectory');
        $stubDirectoryReflection->setAccessible(true);
        $stubDirectoryReflection->setValue($sourceStubber, FileHelper::normalizeWindowsPath(realpath(__DIR__ . '/../../Fixture')));

        $classMapReflection = $stubberReflection->getProperty('classMap');
        $classMapReflection->setAccessible(true);
        $classMapValue                                                     = $classMapReflection->getValue();
        $classMapValue['roave\betterreflectiontest\fixture\fakeconstants'] = '/FakeConstantsStub.php';
        $classMapReflection->setValue($classMapValue);

        $constantMapReflection = $stubberReflection->getProperty('constantMap');
        $constantMapReflection->setAccessible(true);
        $constantMapValue                                                      = $constantMapReflection->getValue();
        $constantMapValue['define_constant']                                   = '/FakeConstantsStub.php';
        $constantMapValue['roave\betterreflectiontest\fixture\const_constant'] = '/FakeConstantsStub.php';
        $constantMapReflection->setValue($constantMapValue);

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

    public function dataClassInPhpVersion(): array
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

    /**
     * @dataProvider dataClassInPhpVersion
     */
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

    public function dataClassConstantInPhpVersion(): array
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

    /**
     * @dataProvider dataClassConstantInPhpVersion
     */
    public function testClassConstantInPhpVersion(string $className, string $constantName, int $phpVersion, bool $isSupported): void
    {
        $sourceStubber            = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $phpInternalSourceLocator = new PhpInternalSourceLocator($this->astLocator, $sourceStubber);
        $reflector                = new DefaultReflector($phpInternalSourceLocator);

        $constants = $reflector->reflectClass($className)->getImmediateConstants();

        self::assertSame($isSupported, array_key_exists($constantName, $constants));
    }

    public function dataMethodInPhpVersion(): array
    {
        return [
            [CoreReflectionProperty::class, 'hasType', 70400, true, 'bool'],
            [CoreReflectionProperty::class, 'hasType', 70300, false],
            [CoreReflectionProperty::class, 'getType', 70400, true, 'ReflectionNamedType|null'],
            [CoreReflectionProperty::class, 'getType', 80000, true, 'ReflectionNamedType|ReflectionUnionType|null'],
            [CoreReflectionProperty::class, 'getType', 80100, true, 'ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null'],
            [CoreReflectionProperty::class, 'getType', 70300, false],
            [CoreReflectionClass::class, 'export', 70400, true],
            [CoreReflectionClass::class, 'export', 80000, false],
            [DatePeriod::class, 'getRecurrences', 70217, true, '?int'],
            [DatePeriod::class, 'getRecurrences', 70216, false],
            [DateTimeInterface::class, 'getOffset', 79999, true, 'int|false'],
            [DateTimeInterface::class, 'getOffset', 80000, true, 'int'],
            [SplFileObject::class, 'fgetss', 79999, true],
            [SplFileObject::class, 'fgetss', 80000, false],
        ];
    }

    /**
     * @dataProvider dataMethodInPhpVersion
     */
    public function testMethodInPhpVersion(string $className, string $methodName, int $phpVersion, bool $isSupported, ?string $returnType = null): void
    {
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
        } else {
            self::assertFalse($class->hasMethod($methodName), $fullMethodName);
        }
    }

    public function dataMethodParameterInPhpVersion(): array
    {
        return [
            ['mysqli_stmt', 'execute', 'params', 80099, false],
            ['mysqli_stmt', 'execute', 'params', 80100, true, '?array', true],
            ['PDOStatement', 'fetchAll', 'fetch_argument', 50299, false],
            ['PDOStatement', 'fetchAll', 'fetch_argument', 50300, true, null, true],
            ['PDOStatement', 'fetchAll', 'fetch_argument', 70499, true, null, true],
            ['PDOStatement', 'fetchAll', 'fetch_argument', 70500, false],
        ];
    }

    /**
     * @dataProvider dataMethodParameterInPhpVersion
     */
    public function testMethodParameterInPhpVersion(
        string $className,
        string $methodName,
        string $parameterName,
        ?int $phpVersion,
        bool $isSupported,
        ?string $type = null,
        ?bool $allowsNull = null,
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

    public function dataPropertyInPhpVersion(): array
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

    /**
     * @dataProvider dataPropertyInPhpVersion
     */
    public function testPropertyInPhpVersion(string $className, string $propertyName, int $phpVersion, bool $isSupported, ?string $type = null): void
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

    public function dataFunctionInPhpVersion(): array
    {
        return [
            ['password_algos', 70400, true],
            ['password_algos', 70300, false],
            ['array_key_first', 70300, true],
            ['array_key_first', 70200, false],
            ['str_starts_with', 80000, true],
            ['str_starts_with', 70400, false],
            ['mysql_query', 50400, true],
            ['mysql_query', 70000, false],
            ['hash_hkdf', 70102, true],
            ['hash_hkdf', 70101, false],
            ['read_exif_data', 79999, true],
            ['read_exif_data', 80000, false],
            // Not core functions
            ['newrelic_add_custom_parameter', 40000, true],
        ];
    }

    /**
     * @dataProvider dataFunctionInPhpVersion
     */
    public function testFunctionInPhpVersion(string $functionName, int $phpVersion, bool $isSupported): void
    {
        $sourceStubber = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);

        $stub = $sourceStubber->generateFunctionStub($functionName);

        if ($isSupported) {
            self::assertNotNull($stub, $functionName);
        } else {
            self::assertNull($stub, $functionName);
        }
    }

    public function dataFunctionParameterInPhpVersion(): array
    {
        return [
            ['bcscale', 'scale', 70200, true, 'int', false],
            ['bcscale', 'scale', 70299, true, 'int', false],
            ['bcscale', 'scale', 70300, true, '?int', true],
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

    /**
     * @dataProvider dataFunctionParameterInPhpVersion
     */
    public function testFunctionParameterInPhpVersion(
        string $functionName,
        string $parameterName,
        ?int $phpVersion,
        bool $isSupported,
        ?string $type = null,
        ?bool $allowsNull = null,
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

    public function dataConstantInPhpVersion(): array
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

    /**
     * @dataProvider dataConstantInPhpVersion
     */
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

    public function dataClassIsDeprecatedInPhpVersion(): array
    {
        return [
            ['Mongo', 70000, true],
            ['Mongo', 80000, true],
            [DateTimeInterface::class, 70400, false],
            [DateTimeInterface::class, 80000, false],
        ];
    }

    /**
     * @dataProvider dataClassIsDeprecatedInPhpVersion
     */
    public function testClassIsDeprecatedInPhpVersion(string $className, int $phpVersion, bool $isDeprecated): void
    {
        $sourceStubber = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $reflector     = new DefaultReflector(new PhpInternalSourceLocator($this->astLocator, $sourceStubber));

        $classReflection = $reflector->reflectClass($className);

        self::assertSame($isDeprecated, $classReflection->isDeprecated());
    }

    public function dataClassConstantIsDeprecatedInPhpVersion(): array
    {
        return [
            ['PDO', 'PARAM_BOOL', 70000, false],
            ['PDO', 'PARAM_BOOL', 80000, false],
            ['PDO', 'PGSQL_ASSOC', 70000, true],
            ['PDO', 'PGSQL_ASSOC', 80000, true],
        ];
    }

    /**
     * @dataProvider dataClassConstantIsDeprecatedInPhpVersion
     */
    public function testClassConstantIsDeprecatedInPhpVersion(string $className, string $constantName, int $phpVersion, bool $isDeprecated): void
    {
        $sourceStubber = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $reflector     = new DefaultReflector(new PhpInternalSourceLocator($this->astLocator, $sourceStubber));

        $classReflection    = $reflector->reflectClass($className);
        $constantReflection = $classReflection->getReflectionConstant($constantName);

        self::assertSame($isDeprecated, $constantReflection->isDeprecated());
    }

    public function dataMethodIsDeprecatedInPhpVersion(): array
    {
        return [
            [CoreReflectionClass::class, 'getName', 70400, false],
            [CoreReflectionClass::class, 'export', 70300, false],
            [CoreReflectionClass::class, 'export', 70399, false],
            [CoreReflectionClass::class, 'export', 70400, true],
        ];
    }

    /**
     * @dataProvider dataMethodIsDeprecatedInPhpVersion
     */
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

    public function dataPropertyIsDeprecatedInPhpVersion(): array
    {
        return [
            ['DateInterval', 'y', 70000, false],
            ['DateInterval', 'y', 80000, false],
            ['DOMDocument', 'actualEncoding', 70000, true],
            ['DOMDocument', 'actualEncoding', 80000, true],
        ];
    }

    /**
     * @dataProvider dataPropertyIsDeprecatedInPhpVersion
     */
    public function testPropertyIsDeprecatedInPhpVersion(string $className, string $propertyName, int $phpVersion, bool $isDeprecated): void
    {
        $sourceStubber = new PhpStormStubsSourceStubber($this->phpParser, $phpVersion);
        $reflector     = new DefaultReflector(new PhpInternalSourceLocator($this->astLocator, $sourceStubber));

        $classReflection    = $reflector->reflectClass($className);
        $propertyReflection = $classReflection->getProperty($propertyName);

        self::assertSame($isDeprecated, $propertyReflection->isDeprecated());
    }

    public function dataFunctionIsDeprecatedInPhpVersion(): array
    {
        return [
            ['strpos', 70000, false],
            ['strpos', 80000, false],
            ['create_function', 70100, false],
            ['create_function', 70199, false],
            ['create_function', 70200, true],
        ];
    }

    /**
     * @dataProvider dataFunctionIsDeprecatedInPhpVersion
     */
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
}
