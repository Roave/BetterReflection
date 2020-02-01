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
use const PHP_VERSION_ID;

/**
 * @covers \Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber
 */
class PhpStormStubsSourceStubberTest extends TestCase
{
    /** @var PhpStormStubsSourceStubber */
    private $sourceStubber;

    /** @var PhpInternalSourceLocator */
    private $phpInternalSourceLocator;

    /** @var ClassReflector */
    private $classReflector;

    /** @var FunctionReflector */
    private $functionReflector;

    /** @var ConstantReflector */
    private $constantReflector;

    protected function setUp() : void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->sourceStubber            = new PhpStormStubsSourceStubber($betterReflection->phpParser());
        $this->phpInternalSourceLocator = new PhpInternalSourceLocator(
            $betterReflection->astLocator(),
            $this->sourceStubber
        );
        $this->classReflector           = new ClassReflector($this->phpInternalSourceLocator);
        $this->functionReflector        = new FunctionReflector($this->phpInternalSourceLocator, $this->classReflector);
        $this->constantReflector        = new ConstantReflector($this->phpInternalSourceLocator, $this->classReflector);
    }

    /**
     * @return string[][]
     */
    public function internalClassesProvider() : array
    {
        $classNames = array_merge(
            get_declared_classes(),
            get_declared_interfaces(),
            get_declared_traits()
        );

        return array_map(
            static function (string $className) : array {
                return [$className];
            },
            array_filter(
                $classNames,
                static function (string $className) : bool {
                    $reflection = new CoreReflectionClass($className);

                    if (! $reflection->isInternal()) {
                        return false;
                    }

                    // Check only always enabled extensions
                    return in_array($reflection->getExtensionName(), ['Core', 'standard', 'pcre', 'SPL'], true);
                }
            )
        );
    }

    /**
     * @dataProvider internalClassesProvider
     */
    public function testInternalClasses(string $className) : void
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

        // Changed in PHP 7.3.0
        if (PHP_VERSION_ID < 70300 && $original->getName() === 'ParseError') {
            return;
        }

        $this->assertSameParentClass($original, $stubbed);
        $this->assertSameInterfaces($original, $stubbed);

        foreach ($original->getMethods() as $method) {
            // Needs fix in JetBrains/phpstorm-stubs
            if ($original->getName() === 'Generator' && $method->getName() === 'throw') {
                continue;
            }

            // Added in PHP 7.4.0
            if (PHP_VERSION_ID < 70400 && $method->getShortName() === '__unserialize') {
                return;
            }

            $this->assertSameMethodAttributes($method, $stubbed->getMethod($method->getName()));
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

        // Needs fixes in JetBrains/phpstorm-stubs
        // self::assertSame($originalParameterNames, $stubParameterNames);

        foreach ($original->getParameters() as $parameter) {
            $stubbedParameter = $stubbed->getParameter($parameter->getName());

            if ($stubbedParameter === null) {
                // Needs fixes in JetBrains/phpstorm-stubs
                continue;
            }

            $this->assertSameParameterAttributes(
                $original,
                $parameter,
                $stubbedParameter
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
        // Inconsistencies
        if (! in_array($parameterName, ['SplFileObject#fputcsv.fields', 'SplFixedArray#fromArray.array'], true)) {
            self::assertSame($original->isArray(), $stubbed->isArray(), $parameterName);
        }

        // Bugs in PHP: https://3v4l.org/RjCDr
        if (! in_array($parameterName, ['Closure#fromCallable.callable', 'CallbackFilterIterator#__construct.callback'], true)) {
            self::assertSame($original->isCallable(), $stubbed->isCallable(), $parameterName);
        }

        self::assertSame($original->canBePassedByValue(), $stubbed->canBePassedByValue(), $parameterName);
        // Bugs in PHP
        if (! in_array($parameterName, [
            'RecursiveIteratorIterator#getSubIterator.level',
            'RecursiveIteratorIterator#setMaxDepth.max_depth',
            'SplTempFileObject#__construct.max_memory',
            'MultipleIterator#__construct.flags',
        ], true)) {
            self::assertSame($original->isOptional(), $stubbed->isOptional(), $parameterName);
        }

        self::assertSame($original->isPassedByReference(), $stubbed->isPassedByReference(), $parameterName);
        self::assertSame($original->isVariadic(), $stubbed->isVariadic(), $parameterName);

        $class = $original->getClass();
        if ($class) {
            // Not possible to write "RecursiveIterator|IteratorAggregate" in PHP code in JetBrains/phpstorm-stubs
            if ($parameterName !== 'RecursiveTreeIterator#__construct.iterator') {
                $stubbedClass = $stubbed->getClass();

                self::assertInstanceOf(ReflectionClass::class, $stubbedClass, $parameterName);
                self::assertSame($class->getName(), $stubbedClass->getName(), $parameterName);
            }
        } else {
            // Bugs in PHP
            if (! in_array($parameterName, [
                'Error#__construct.previous',
                'Exception#__construct.previous',
                'Closure#bind.closure',
            ], true)) {
                self::assertNull($stubbed->getClass(), $parameterName);
            }
        }
    }

    /**
     * @return string[][]
     */
    public function internalFunctionsProvider() : array
    {
        $functionNames = get_defined_functions()['internal'];

        // Needs fixes in JetBrains/phpstorm-stubs
        $missingFunctionsInStubs = ['sapi_windows_set_ctrl_handler', 'sapi_windows_generate_ctrl_event'];

        return array_map(
            static function (string $functionName) : array {
                return [$functionName];
            },
            array_filter(
                $functionNames,
                static function (string $functionName) use ($missingFunctionsInStubs) : bool {
                    if (in_array($functionName, $missingFunctionsInStubs, true)) {
                        return false;
                    }

                    $reflection = new CoreReflectionFunction($functionName);

                    // Check only always enabled extensions
                    return in_array($reflection->getExtensionName(), ['Core', 'standard', 'pcre', 'SPL'], true);
                }
            )
        );
    }

    /**
     * @dataProvider internalFunctionsProvider
     */
    public function testInternalFunctions(string $functionName) : void
    {
        $stubbedReflection = $this->functionReflector->reflect($functionName);

        self::assertSame($functionName, $stubbedReflection->getName());
        self::assertTrue($stubbedReflection->isInternal());
        self::assertFalse($stubbedReflection->isUserDefined());

        $originalReflection = new CoreReflectionFunction($functionName);

        // Needs fixes in JetBrains/phpstorm-stubs
        if (in_array($functionName, [
            'setlocale',
            'sprintf',
            'printf',
            'fprintf',
            'trait_exists',
            'user_error',
            'preg_replace_callback_array',
            'strtok',
            'strtr',
            'hrtime',
            'forward_static_call',
            'forward_static_call_array',
            'pack',
            'min',
            'max',
            'var_dump',
            'register_shutdown_function',
            'register_tick_function',
            'compact',
            'array_map',
            'array_merge',
            'array_merge_recursive',
            'array_replace',
            'array_replace_recursive',
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
            'dns_get_record',
            'extract',
            'pos',
            'setcookie',
            'setrawcookie',
            'sapi_windows_vt100_support',
        ], true)) {
            return;
        }

        // Changed in PHP 7.3.0
        if (PHP_VERSION_ID < 70300 && in_array($functionName, ['array_push', 'array_unshift'], true)) {
            return;
        }

        // Changed in PHP 7.4.0
        if (PHP_VERSION_ID < 70400 && $functionName === 'preg_replace_callback') {
            return;
        }

        // Needs fixes in JetBrains/phpstorm-stubs or PHP
        if (in_array($functionName, ['get_resources', 'sapi_windows_cp_get', 'stream_context_set_option'], true)) {
            return;
        }

        self::assertSame($originalReflection->getNumberOfParameters(), $stubbedReflection->getNumberOfParameters());
        self::assertSame($originalReflection->getNumberOfRequiredParameters(), $stubbedReflection->getNumberOfRequiredParameters());

        $stubbedReflectionParameters = $stubbedReflection->getParameters();
        foreach ($originalReflection->getParameters() as $parameterNo => $originalReflectionParameter) {
            $parameterName = sprintf('%s.%s', $functionName, $originalReflectionParameter->getName());

            $stubbedReflectionParameter = $stubbedReflectionParameters[$parameterNo];

            self::assertSame($originalReflectionParameter->isOptional(), $stubbedReflectionParameter->isOptional(), $parameterName);
            self::assertSame($originalReflectionParameter->isPassedByReference(), $stubbedReflectionParameter->isPassedByReference(), $parameterName);
            self::assertSame($originalReflectionParameter->canBePassedByValue(), $stubbedReflectionParameter->canBePassedByValue(), $parameterName);

            // Bugs in PHP
            if (! in_array($parameterName, ['preg_replace_callback.callback', 'header_register_callback.callback'], true)) {
                self::assertSame($originalReflectionParameter->isCallable(), $stubbedReflectionParameter->isCallable(), $parameterName);
            }

            // Needs fixes in JetBrains/phpstorm-stubs
            if (! in_array($parameterName, ['fscanf.vars', 'debug_zval_dump.vars'], true)) {
                self::assertSame($originalReflectionParameter->isVariadic(), $stubbedReflectionParameter->isVariadic(), $parameterName);
            }

            $class = $originalReflectionParameter->getClass();
            if ($class) {
                // Needs fixes in JetBrains/phpstorm-stubs
                if (! in_array($parameterName, [
                    'iterator_to_array.iterator',
                    'iterator_count.iterator',
                    'iterator_apply.iterator',
                ], true)) {
                    $stubbedClass = $stubbedReflectionParameter->getClass();
                    self::assertInstanceOf(ReflectionClass::class, $stubbedClass, $parameterName);
                    self::assertSame($class->getName(), $stubbedClass->getName(), $parameterName);
                }
            } else {
                self::assertNull($originalReflectionParameter->getClass(), $parameterName);
            }
        }
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function internalConstantsProvider() : array
    {
        $provider = [];

        /** @psalm-var array<string, array<string,int|string|float|bool|null|array|resource>> $constants */
        $constants = get_defined_constants(true);

        foreach ($constants as $extensionName => $extensionConstants) {
            // Check only always enabled extensions
            if (! in_array($extensionName, ['Core', 'standard', 'pcre', 'SPL'], true)) {
                continue;
            }

            foreach ($extensionConstants as $constantName => $constantValue) {
                // Needs fixes in JetBrains/phpstorm-stubs
                if (in_array($constantName, [
                    'PHP_WINDOWS_NT_DOMAIN_CONTROLLER',
                    'PHP_WINDOWS_NT_SERVER',
                    'PHP_WINDOWS_NT_WORKSTATION',
                    'PHP_WINDOWS_EVENT_CTRL_C',
                    'PHP_WINDOWS_EVENT_CTRL_BREAK',
                ], true)) {
                    continue;
                }

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
     * @param mixed $constantValue
     *
     * @dataProvider internalConstantsProvider
     */
    public function testInternalConstants(string $constantName, $constantValue, string $extensionName) : void
    {
        $constantReflection = $this->constantReflector->reflect($constantName);

        self::assertInstanceOf(ReflectionConstant::class, $constantReflection);
        // Needs fixes in JetBrains/phpstorm-stubs
        if (! in_array($constantName, ['TRUE', 'FALSE', 'NULL'], true)) {
            self::assertSame($constantName, $constantReflection->getName());
            self::assertSame($constantName, $constantReflection->getShortName());
        }

        self::assertNotNull($constantReflection->getNamespaceName());
        self::assertFalse($constantReflection->inNamespace());
        self::assertTrue($constantReflection->isInternal());
        self::assertFalse($constantReflection->isUserDefined());
        // Needs fixes in JetBrains/phpstorm-stubs
        // self::assertSame($extensionName, $constantReflection->getExtensionName());
        // NAN cannot be compared
        if ($constantName === 'NAN') {
            return;
        }

        self::assertSame($constantValue, $constantReflection->getValue());
    }

    public function testNoStubForUnknownClass() : void
    {
        self::assertNull($this->sourceStubber->generateClassStub('SomeClass'));
    }

    public function testNoStubForUnknownFunction() : void
    {
        self::assertNull($this->sourceStubber->generateFunctionStub('someFunction'));
    }

    public function testNoStubForUnknownConstant() : void
    {
        self::assertNull($this->sourceStubber->generateConstantStub('SOME_CONSTANT'));
    }
}
