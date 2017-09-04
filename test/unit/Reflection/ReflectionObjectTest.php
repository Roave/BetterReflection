<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use InvalidArgumentException;
use LogicException;
use PhpParser\Node;
use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
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
use stdClass;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionObject
 */
class ReflectionObjectTest extends TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @return Node[]
     */
    private function parse(string $code) : array
    {
        return BetterReflectionSingleton::instance()->phpParser()->parse($code);
    }

    public function testExceptionThrownWhenNonObjectGiven() : void
    {
        $this->expectException(InvalidArgumentException::class);
        ReflectionObject::createFromInstance(123);
    }

    public function anonymousClassInstancesProvider() : array
    {
        $file = FileHelper::normalizeWindowsPath(\realpath(__DIR__ . '/../Fixture/AnonymousClassInstances.php'));

        $anonymousClasses = require $file;

        return [
            [$anonymousClasses[0], $file, 3, 9],
            [$anonymousClasses[1], $file, 11, 17],
        ];
    }

    /**
     * @param object $anonymousClass
     * @param string $file
     * @param int $startLine
     * @param int $endLine
     * @dataProvider anonymousClassInstancesProvider
     */
    public function testReflectionForAnonymousClass($anonymousClass, string $file, int $startLine, int $endLine) : void
    {
        $classInfo = ReflectionObject::createFromInstance($anonymousClass);

        self::assertTrue($classInfo->isAnonymous());
        self::assertFalse($classInfo->inNamespace());
        self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $classInfo->getName());
        self::assertSame($file, $classInfo->getFileName());
        self::assertSame($startLine, $classInfo->getStartLine());
        self::assertSame($endLine, $classInfo->getEndLine());
    }

    public function testReflectionWorksWithInternalClasses() : void
    {
        $foo = new stdClass();

        $classInfo = ReflectionObject::createFromInstance($foo);
        self::assertInstanceOf(ReflectionObject::class, $classInfo);
        self::assertSame('stdClass', $classInfo->getName());
        self::assertTrue($classInfo->isInternal());
    }

    public function testReflectionWorksWithEvaledClasses() : void
    {
        $foo = new ClassForHinting();

        $classInfo = ReflectionObject::createFromInstance($foo);
        self::assertInstanceOf(ReflectionObject::class, $classInfo);
        self::assertSame(ClassForHinting::class, $classInfo->getName());
        self::assertFalse($classInfo->isInternal());
    }

    public function testReflectionWorksWithDynamicallyDeclaredMembers() : void
    {
        $foo      = new ClassForHinting();
        $foo->bar = 'huzzah';

        $classInfo = ReflectionObject::createFromInstance($foo);
        $propInfo  = $classInfo->getProperty('bar');

        self::assertInstanceOf(ReflectionProperty::class, $propInfo);
        self::assertSame('bar', $propInfo->getName());
        self::assertFalse($propInfo->isDefault());
    }

    public function testExceptionThrownWhenInvalidInstanceGiven() : void
    {
        $foo      = new ClassForHinting();
        $foo->bar = 'huzzah';

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

    public function testGetRuntimePropertiesWithFilter() : void
    {
        $foo      = new stdClass();
        $foo->bar = 'huzzah';

        $classInfo = ReflectionObject::createFromInstance($foo);

        self::assertEmpty($classInfo->getProperties(CoreReflectionProperty::IS_STATIC));
        self::assertCount(1, $classInfo->getProperties(CoreReflectionProperty::IS_PUBLIC));
        self::assertEmpty($classInfo->getProperties(CoreReflectionProperty::IS_PROTECTED));
        self::assertEmpty($classInfo->getProperties(CoreReflectionProperty::IS_PRIVATE));
    }

    public function testGetRuntimeImmediatePropertiesWithFilter() : void
    {
        $foo      = new stdClass();
        $foo->bar = 'huzzah';

        $classInfo = ReflectionObject::createFromInstance($foo);

        self::assertEmpty($classInfo->getImmediateProperties(CoreReflectionProperty::IS_STATIC));
        self::assertCount(1, $classInfo->getImmediateProperties(CoreReflectionProperty::IS_PUBLIC));
        self::assertEmpty($classInfo->getImmediateProperties(CoreReflectionProperty::IS_PROTECTED));
        self::assertEmpty($classInfo->getImmediateProperties(CoreReflectionProperty::IS_PRIVATE));
    }

    /**
     * This data provider gets all the public methods from ReflectionClass, but
     * filters out a few methods we want to test manually
     *
     * @return array
     */
    public function reflectionClassMethodProvider() : array
    {
        $publicClassMethods = \get_class_methods(ReflectionClass::class);

        $ignoreMethods = [
            'createFromName',
            'createFromNode',
            'createFromInstance',
            '__toString',
            'export',
            '__clone',
        ];

        $filteredMethods = [];
        foreach ($publicClassMethods as $method) {
            if ( ! \in_array($method, $ignoreMethods, true)) {
                $filteredMethods[$method] = [$method];
            }
        }

        return $filteredMethods;
    }

    /**
     * This test loops through the DataProvider (which provides a list of public
     * methods from ReflectionClass), ensures the method exists in ReflectionObject
     * and that when the method is called on ReflectionObject, the method of the
     * same name on ReflectionClass is also called.
     *
     * @param string $methodName
     * @dataProvider reflectionClassMethodProvider
     */
    public function testReflectionObjectOverridesAllMethodsInReflectionClass(string $methodName) : void
    {
        // First, ensure the expected method even exists
        $publicObjectMethods = \get_class_methods(ReflectionObject::class);
        self::assertContains($methodName, $publicObjectMethods);

        // Create a mock that will be used to assert that the named method will
        // be called when we call the same method on ReflectionObject
        $mockReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->setMethods([$methodName])
            ->getMock();
        $mockReflectionClass
            ->expects($this->atLeastOnce())
            ->method($methodName);

        // Force inject node and locatedSource properties on our ReflectionClass
        // mock so that methods will not fail when they are accessed
        $mockReflectionClassReflection = new CoreReflectionClass(ReflectionClass::class);

        $php = '<?php class stdClass {}';

        $mockReflectionClassNodeReflection = $mockReflectionClassReflection->getProperty('locatedSource');
        $mockReflectionClassNodeReflection->setAccessible(true);
        $mockReflectionClassNodeReflection->setValue($mockReflectionClass, new EvaledLocatedSource($php));

        $mockReflectionClassNodeReflection = $mockReflectionClassReflection->getProperty('node');
        $mockReflectionClassNodeReflection->setAccessible(true);
        $mockReflectionClassNodeReflection->setValue($mockReflectionClass, $this->parse($php)[0]);

        // Create the ReflectionObject from a dummy class
        $reflectionObject = ReflectionObject::createFromInstance(new stdClass());

        // Override the reflectionClass property on the ReflectionObject to use
        // the mocked reflectionclass above
        $reflectionObjectReflection                        = new CoreReflectionObject($reflectionObject);
        $reflectionObjectReflectionClassPropertyReflection = $reflectionObjectReflection->getProperty('reflectionClass');
        $reflectionObjectReflectionClassPropertyReflection->setAccessible(true);
        $reflectionObjectReflectionClassPropertyReflection->setValue($reflectionObject, $mockReflectionClass);

        $reflectionObjectReflectionMethod = $reflectionObjectReflection->getMethod($methodName);
        $fakeParams                       = \array_map(
            function (ReflectionParameter $parameter) {
                switch ((string) $parameter->getType()) {
                    case 'int':
                        return \random_int(1, 1000);
                    case 'null':
                        return null;
                    case 'bool':
                        return (bool) \random_int(0, 1);
                    default:
                        return \uniqid('stringParam', true);
                }
            },
            $reflectionObjectReflectionMethod->getParameters()
        );

        // Finally, call the method name with some dummy parameters. This should
        // ensure that the method of the same name gets called on the
        // $mockReflectionClass mock (as we expect $methodName to be called)
        $reflectionObject->{$methodName}(...$fakeParams);
    }

    public function testCreateFromNameThrowsException() : void
    {
        $this->expectException(LogicException::class);
        ReflectionObject::createFromName('foo');
    }

    public function testReflectionObjectExportMatchesExpectation() : void
    {
        $foo      = new ClassForHinting();
        $foo->bar = 'huzzah';

        $expectedExport = <<<'BLAH'
Object of class [ <user> class Roave\BetterReflectionTest\Fixture\ClassForHinting ] {
  @@ %s

  - Constants [0] {
  }

  - Static properties [0] {
  }

  - Static methods [0] {
  }

  - Properties [1] {
    Property [ <default> public $someProperty ]
  }

  - Dynamic properties [1] {
    Property [ <dynamic> public $bar ]
  }

  - Methods [0] {
  }
}
BLAH;
        $actualExport   = ReflectionObject::export($foo);

        self::assertStringMatchesFormat($expectedExport, $actualExport);
    }

    public function testCannotClone() : void
    {
        $classInfo = ReflectionObject::createFromInstance(new stdClass());

        $this->expectException(Uncloneable::class);
        $unused = clone $classInfo;
    }
}
