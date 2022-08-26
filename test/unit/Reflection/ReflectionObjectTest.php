<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use InvalidArgumentException;
use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionNamedType;
use ReflectionObject as CoreReflectionObject;
use ReflectionParameter;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionObject;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\SourceLocator\Located\EvaledLocatedSource;
use Roave\BetterReflection\Util\FileHelper;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\ClassForHinting;
use Roave\BetterReflectionTest\Fixture\DefaultProperties;
use Roave\BetterReflectionTest\Fixture\FixtureInterfaceRequire;
use Roave\BetterReflectionTest\Fixture\RuntimeProperties;
use stdClass;

use function array_map;
use function get_class_methods;
use function in_array;
use function random_int;
use function realpath;
use function uniqid;

/** @covers \Roave\BetterReflection\Reflection\ReflectionObject */
class ReflectionObjectTest extends TestCase
{
    /** @return Node[] */
    private function parse(string $code): array
    {
        return BetterReflectionSingleton::instance()->phpParser()->parse($code);
    }

    /** @return list<array{0: object, 1: string, 2: int, 3: int}> */
    public function anonymousClassInstancesProvider(): array
    {
        $file = FileHelper::normalizeWindowsPath(realpath(__DIR__ . '/../Fixture/AnonymousClassInstances.php'));

        $anonymousClasses = require $file;

        return [
            [$anonymousClasses[0], $file, 3, 9],
            [$anonymousClasses[1], $file, 11, 17],
        ];
    }

    /** @dataProvider anonymousClassInstancesProvider */
    public function testReflectionForAnonymousClass(object $anonymousClass, string $file, int $startLine, int $endLine): void
    {
        $classInfo = ReflectionObject::createFromInstance($anonymousClass);

        self::assertTrue($classInfo->isAnonymous());
        self::assertFalse($classInfo->inNamespace());
        self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $classInfo->getName());
        self::assertSame($file, $classInfo->getFileName());
        self::assertSame($startLine, $classInfo->getStartLine());
        self::assertSame($endLine, $classInfo->getEndLine());
    }

    public function testReflectionForAnonymousClassWithInterface(): void
    {
        $file = FileHelper::normalizeWindowsPath(realpath(__DIR__ . '/../Fixture/AnonymousClassInstanceWithInterfaceForRequire.php'));

        $anonymousClass = require $file;

        $classInfo = ReflectionObject::createFromInstance($anonymousClass);

        self::assertTrue($classInfo->isAnonymous());
        self::assertFalse($classInfo->inNamespace());
        self::assertStringStartsWith(FixtureInterfaceRequire::class, $classInfo->getName());
        self::assertContains(FixtureInterfaceRequire::class, $classInfo->getInterfaceNames());
        self::assertTrue($classInfo->isInstantiable());
    }

    public function testReflectionWorksWithInternalClasses(): void
    {
        $foo = new stdClass();

        $classInfo = ReflectionObject::createFromInstance($foo);
        self::assertInstanceOf(ReflectionObject::class, $classInfo);
        self::assertSame('stdClass', $classInfo->getName());
        self::assertTrue($classInfo->isInternal());
        self::assertSame('Core', $classInfo->getExtensionName());
    }

    public function testReflectionWorksWithEvaledClasses(): void
    {
        $foo = new ClassForHinting();

        $classInfo = ReflectionObject::createFromInstance($foo);
        self::assertInstanceOf(ReflectionObject::class, $classInfo);
        self::assertSame(ClassForHinting::class, $classInfo->getName());
        self::assertFalse($classInfo->isInternal());
        self::assertNull($classInfo->getExtensionName());
    }

    public function testReflectionWorksWithDynamicallyDeclaredMembers(): void
    {
        $foo      = new RuntimeProperties();
        $foo->bar = 'huzzah'; // @phpstan-ignore-line
        $foo->baz = 'bazzah'; // @phpstan-ignore-line

        $classInfo = ReflectionObject::createFromInstance($foo);

        self::assertCount(6, $classInfo->getProperties());
        self::assertCount(6, $classInfo->getImmediateProperties());

        self::assertTrue($classInfo->hasProperty('bar'));

        $propInfo = $classInfo->getProperty('bar');

        self::assertInstanceOf(ReflectionProperty::class, $propInfo);
        self::assertSame('bar', $propInfo->getName());
        self::assertFalse($propInfo->isDefault());
        self::assertTrue($propInfo->isPublic());
        self::assertSame('huzzah', $propInfo->getDefaultValue());
        self::assertSame(0, $propInfo->getPositionInAst());
        self::assertFalse($propInfo->isPromoted());
    }

    public function testExceptionThrownWhenInvalidInstanceGiven(): void
    {
        $foo      = new RuntimeProperties();
        $foo->bar = 'huzzah'; // @phpstan-ignore-line

        $classInfo = ReflectionObject::createFromInstance($foo);

        $mockClass = $this->createMock(ReflectionClass::class);

        $reflectionObjectReflection = new CoreReflectionObject($classInfo);

        $reflectionObjectObjectReflection = $reflectionObjectReflection->getProperty('object');
        $reflectionObjectObjectReflection->setAccessible(true);
        $reflectionObjectObjectReflection->setValue($classInfo, new stdClass());

        $reflectionObjectReflectionClassReflection = $reflectionObjectReflection->getProperty('reflectionClass');
        $reflectionObjectReflectionClassReflection->setAccessible(true);
        $reflectionObjectReflectionClassReflection->setValue($classInfo, $mockClass);

        $this->expectException(InvalidArgumentException::class);
        $classInfo->getProperties();
    }

    public function testGetRuntimePropertiesWithFilter(): void
    {
        $foo      = new RuntimeProperties();
        $foo->bar = 'huzzah'; // @phpstan-ignore-line
        $foo->baz = 'bazzah'; // @phpstan-ignore-line

        $classInfo = ReflectionObject::createFromInstance($foo);

        self::assertCount(1, $classInfo->getProperties(CoreReflectionProperty::IS_STATIC));
        self::assertCount(4, $classInfo->getProperties(CoreReflectionProperty::IS_PUBLIC));
        self::assertCount(1, $classInfo->getProperties(CoreReflectionProperty::IS_PROTECTED));
        self::assertCount(1, $classInfo->getProperties(CoreReflectionProperty::IS_PRIVATE));
    }

    public function testGetRuntimeImmediatePropertiesWithFilter(): void
    {
        $foo      = new RuntimeProperties();
        $foo->bar = 'huzzah'; // @phpstan-ignore-line
        $foo->baz = 'bazzah'; // @phpstan-ignore-line

        $classInfo = ReflectionObject::createFromInstance($foo);

        self::assertCount(1, $classInfo->getProperties(CoreReflectionProperty::IS_STATIC));
        self::assertCount(4, $classInfo->getProperties(CoreReflectionProperty::IS_PUBLIC));
        self::assertCount(1, $classInfo->getProperties(CoreReflectionProperty::IS_PROTECTED));
        self::assertCount(1, $classInfo->getProperties(CoreReflectionProperty::IS_PRIVATE));
    }

    public function testRuntimePropertyCannotBePromoted(): void
    {
        $foo      = new RuntimeProperties();
        $foo->bar = 'huzzah'; // @phpstan-ignore-line

        $classInfo    = ReflectionObject::createFromInstance($foo);
        $propertyInfo = $classInfo->getProperty('bar');

        self::assertInstanceOf(ReflectionProperty::class, $propertyInfo);
        self::assertFalse($propertyInfo->isPromoted());
        self::assertSame(0, $propertyInfo->getPositionInAst());
    }

    public function testGetDefaultPropertiesShouldIgnoreRuntimeProperty(): void
    {
        $object                     = new DefaultProperties();
        $object->notDefaultProperty = null; // @phpstan-ignore-line

        $classInfo = ReflectionObject::createFromInstance($object);

        self::assertSame([
            'fromTrait' => 'anything',
            'hasDefault' => 'const',
            'hasNullAsDefault' => null,
            'noDefault' => null,
            'hasDefaultWithType' => 123,
            'hasNullAsDefaultWithType' => null,
            'noDefaultWithType' => null,
        ], $classInfo->getDefaultProperties());
    }

    /**
     * This data provider gets all the public methods from ReflectionClass, but
     * filters out a few methods we want to test manually
     *
     * @return array<string, array{0: string}>
     */
    public function reflectionClassMethodProvider(): array
    {
        $publicClassMethods = get_class_methods(ReflectionClass::class);

        $ignoreMethods = [
            'createFromName',
            'createFromNode',
            'createFromInstance',
            'getDefaultProperties',
            '__toString',
            '__clone',
        ];

        $filteredMethods = [];
        foreach ($publicClassMethods as $method) {
            if (in_array($method, $ignoreMethods, true)) {
                continue;
            }

            $filteredMethods[$method] = [$method];
        }

        return $filteredMethods;
    }

    /**
     * This test loops through the DataProvider (which provides a list of public
     * methods from ReflectionClass), ensures the method exists in ReflectionObject
     * and that when the method is called on ReflectionObject, the method of the
     * same name on ReflectionClass is also called.
     *
     * @dataProvider reflectionClassMethodProvider
     */
    public function testReflectionObjectOverridesAllMethodsInReflectionClass(string $methodName): void
    {
        // First, ensure the expected method even exists
        $publicObjectMethods = get_class_methods(ReflectionObject::class);
        self::assertContains($methodName, $publicObjectMethods);

        // Create a mock that will be used to assert that the named method will
        // be called when we call the same method on ReflectionObject
        $mockReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->setMethods([$methodName])
            ->getMock();

        $method = $mockReflectionClass
            ->expects($this->atLeastOnce())
            ->method($methodName);

        $php  = '<?php class stdClass {}';
        $node = $this->parse($php)[0];

        // Cannot be generated because the declared return type is a union, we have to provide a return value
        if ($methodName === 'getAst') {
            $method->willReturn($node);
        }

        // Force inject node and locatedSource properties on our ReflectionClass
        // mock so that methods will not fail when they are accessed
        $mockReflectionClassReflection = new CoreReflectionClass(ReflectionClass::class);

        $mockReflectionClassNodeReflection = $mockReflectionClassReflection->getProperty('locatedSource');
        $mockReflectionClassNodeReflection->setAccessible(true);
        $mockReflectionClassNodeReflection->setValue($mockReflectionClass, new EvaledLocatedSource($php, 'stdClass'));

        $mockReflectionClassNodeReflection = $mockReflectionClassReflection->getProperty('node');
        $mockReflectionClassNodeReflection->setAccessible(true);
        $mockReflectionClassNodeReflection->setValue($mockReflectionClass, $node);

        $mockReflectionClassNodeReflection = $mockReflectionClassReflection->getProperty('declaringNamespace');
        $mockReflectionClassNodeReflection->setAccessible(true);
        $mockReflectionClassNodeReflection->setValue($mockReflectionClass, null);

        // Create the ReflectionObject from a dummy class
        $reflectionObject = ReflectionObject::createFromInstance(new stdClass());

        // Override the reflectionClass property on the ReflectionObject to use
        // the mocked reflectionclass above
        $reflectionObjectReflection                        = new CoreReflectionObject($reflectionObject);
        $reflectionObjectReflectionClassPropertyReflection = $reflectionObjectReflection->getProperty('reflectionClass');
        $reflectionObjectReflectionClassPropertyReflection->setAccessible(true);
        $reflectionObjectReflectionClassPropertyReflection->setValue($reflectionObject, $mockReflectionClass);

        $reflectionObjectReflectionMethod = $reflectionObjectReflection->getMethod($methodName);
        $fakeParams                       = array_map(
            static function (ReflectionParameter $parameter) use ($methodName) {
                if ($methodName === 'isInstance' && $parameter->getName() === 'object') {
                    return new stdClass();
                }

                $type     = $parameter->getType();
                $typeName = $type instanceof ReflectionNamedType ? $type->getName() : (string) $type;

                switch ($typeName) {
                    case 'int':
                        return random_int(1, 1000);

                    case 'null':
                        return null;

                    case 'bool':
                        return (bool) random_int(0, 1);

                    default:
                        return uniqid('stringParam', true);
                }
            },
            $reflectionObjectReflectionMethod->getParameters(),
        );

        // Finally, call the method name with some dummy parameters. This should
        // ensure that the method of the same name gets called on the
        // $mockReflectionClass mock (as we expect $methodName to be called)
        $reflectionObject->{$methodName}(...$fakeParams);
    }

    public function testCannotClone(): void
    {
        $classInfo = ReflectionObject::createFromInstance(new stdClass());

        $this->expectException(Uncloneable::class);
        clone $classInfo;
    }
}
