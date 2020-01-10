<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\SourceStubber;

use ClassWithoutNamespaceForSourceStubber;
use Closure;
use PHPUnit\Framework\TestCase;
use RecursiveArrayIterator;
use ReflectionClass as CoreReflectionClass;
use ReflectionException;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionParameter as CoreReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\ConstantReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\ClassForSourceStubber;
use Roave\BetterReflectionTest\Fixture\EmptyTrait;
use Roave\BetterReflectionTest\Fixture\InterfaceForSourceStubber;
use Roave\BetterReflectionTest\Fixture\TraitForSourceStubber;
use Traversable;
use function array_filter;
use function array_map;
use function array_merge;
use function get_declared_classes;
use function get_declared_interfaces;
use function get_declared_traits;
use function in_array;
use function sort;
use function sprintf;
use const PHP_VERSION_ID;

/**
 * @covers \Roave\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber
 */
class ReflectionSourceStubberTest extends TestCase
{
    /** @var ReflectionSourceStubber */
    private $stubber;

    /** @var PhpInternalSourceLocator */
    private $phpInternalSourceLocator;

    /** @var ClassReflector */
    private $classReflector;

    protected function setUp() : void
    {
        parent::setUp();

        $this->stubber                  = new ReflectionSourceStubber();
        $this->phpInternalSourceLocator = new PhpInternalSourceLocator(
            BetterReflectionSingleton::instance()->astLocator(),
            $this->stubber
        );
        $this->classReflector           = new ClassReflector($this->phpInternalSourceLocator);
    }

    public function testCanStubClass() : void
    {
        $stubData = $this->stubber->generateClassStub('stdClass');

        self::assertNotNull($stubData);
        self::assertStringMatchesFormat(
            '%Aclass stdClass%A{%A}%A',
            $stubData->getStub()
        );
        self::assertSame('Core', $stubData->getExtensionName());
    }

    public function testCanStubInterface() : void
    {
        $stubData = $this->stubber->generateClassStub(Traversable::class);

        self::assertNotNull($stubData);
        self::assertStringMatchesFormat(
            '%Ainterface Traversable%A{%A}%A',
            $stubData->getStub()
        );
        self::assertSame('Core', $stubData->getExtensionName());
    }

    public function testCanStubTraits() : void
    {
        require __DIR__ . '/../../Fixture/EmptyTrait.php';

        $stubData = $this->stubber->generateClassStub(EmptyTrait::class);

        self::assertNotNull($stubData);
        self::assertStringMatchesFormat(
            '%Atrait EmptyTrait%A{%A}%A',
            $stubData->getStub()
        );
        self::assertNull($stubData->getExtensionName());
    }

    public function testClassStub() : void
    {
        require __DIR__ . '/../../Fixture/ClassForSourceStubber.php';

        $stubData = $this->stubber->generateClassStub(ClassForSourceStubber::class);

        self::assertNotNull($stubData);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/ClassForSourceStubberExpected.php', $stubData->getStub());
        self::assertNull($stubData->getExtensionName());
    }

    public function testClassWithoutNamespaceStub() : void
    {
        require __DIR__ . '/../../Fixture/ClassWithoutNamespaceForSourceStubber.php';

        $stubData = $this->stubber->generateClassStub(ClassWithoutNamespaceForSourceStubber::class);

        self::assertNotNull($stubData);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/ClassWithoutNamespaceForSourceStubberExpected.php', $stubData->getStub());
        self::assertNull($stubData->getExtensionName());
    }

    public function testInterfaceStub() : void
    {
        require __DIR__ . '/../../Fixture/InterfaceForSourceStubber.php';

        $stubData = $this->stubber->generateClassStub(InterfaceForSourceStubber::class);

        self::assertNotNull($stubData);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/InterfaceForSourceStubberExpected.php', $stubData->getStub());
        self::assertNull($stubData->getExtensionName());
    }

    public function testTraitStub() : void
    {
        require __DIR__ . '/../../Fixture/TraitForSourceStubber.php';

        $stubData = $this->stubber->generateClassStub(TraitForSourceStubber::class);

        self::assertNotNull($stubData);
        self::assertStringEqualsFile(__DIR__ . '/../../Fixture/TraitForSourceStubberExpected.php', $stubData->getStub());
        self::assertNull($stubData->getExtensionName());
    }

    /**
     * @return string[][]
     */
    public function internalClassesProvider() : array
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
                    return (new CoreReflectionClass($symbol))->isInternal();
                }
            )
        );
    }

    /**
     * @throws ReflectionException
     *
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

        $this->assertSameParentClass($original, $stubbed);
        $this->assertSameInterfaces($original, $stubbed);

        foreach ($original->getMethods() as $method) {
            $this->assertSameMethodAttributes($method, $stubbed->getMethod($method->getName()));
        }

        if ($original->getName() === RecursiveArrayIterator::class
            && PHP_VERSION_ID >= 70200
            && PHP_VERSION_ID < 70202
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

    public function testFunctionWithParameterPassedByReference() : void
    {
        $reflector          = new FunctionReflector($this->phpInternalSourceLocator, $this->classReflector);
        $functionReflection = $reflector->reflect('sort');

        self::assertSame('sort', $functionReflection->getName());
        self::assertSame(2, $functionReflection->getNumberOfParameters());

        $parameterReflection = $functionReflection->getParameters()[0];
        self::assertSame('arg', $parameterReflection->getName());
        self::assertFalse($parameterReflection->isOptional());
        self::assertTrue($parameterReflection->isPassedByReference());
        self::assertFalse($parameterReflection->canBePassedByValue());
    }

    public function testFunctionWithOptionalParameter() : void
    {
        $reflector          = new FunctionReflector($this->phpInternalSourceLocator, $this->classReflector);
        $functionReflection = $reflector->reflect('preg_match');

        self::assertSame('preg_match', $functionReflection->getName());
        self::assertSame(5, $functionReflection->getNumberOfParameters());
        self::assertSame(2, $functionReflection->getNumberOfRequiredParameters());

        $parameterReflection = $functionReflection->getParameters()[2];
        self::assertSame('subpatterns', $parameterReflection->getName());
        self::assertTrue($parameterReflection->isOptional());
    }

    public function variadicParametersProvider() : array
    {
        return [
            ['sprintf', 1, true, true],
            ['printf', 1, true, true],
        ];
    }

    /**
     * @dataProvider variadicParametersProvider
     */
    public function testFunctionWithVariadicParameter(string $functionName, int $parameterPosition, bool $parameterIsVariadic, bool $parameterIsOptional) : void
    {
        $reflector          = new FunctionReflector($this->phpInternalSourceLocator, $this->classReflector);
        $functionReflection = $reflector->reflect($functionName);

        self::assertSame($functionName, $functionReflection->getName());

        $parametersReflections = $functionReflection->getParameters();
        self::assertArrayHasKey($parameterPosition, $parametersReflections);
        self::assertSame($parameterIsVariadic, $parametersReflections[$parameterPosition]->isVariadic());
        self::assertSame($parameterIsOptional, $parametersReflections[$parameterPosition]->isOptional());
    }

    public function testCanStubConstant() : void
    {
        $stubData = $this->stubber->generateConstantStub('E_ALL');

        self::assertNotNull($stubData);
        self::assertStringMatchesFormat(
            "%Adefine('E_ALL',%A",
            $stubData->getStub()
        );
        self::assertSame('Core', $stubData->getExtensionName());
    }

    public function testUnknownConstant() : void
    {
        self::assertNull($this->stubber->generateConstantStub('SOME_CONSTANT'));
    }

    public function unsupportedConstants() : array
    {
        return [
            ['STDIN'],
            ['STDOUT'],
            ['STDERR'],
        ];
    }

    /**
     * @dataProvider unsupportedConstants
     */
    public function testUnsupportedConstants(string $constantName) : void
    {
        self::expectException(IdentifierNotFound::class);

        $reflector = new ConstantReflector($this->phpInternalSourceLocator, $this->classReflector);
        $reflector->reflect($constantName);
    }
}
