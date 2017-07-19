<?php

namespace Roave\BetterReflectionTest\Reflection;

use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionObject;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\EvaledLocatedSource;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflectionTest\Fixture\ClassForHinting;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionObject
 */
class ReflectionObjectTest extends \PHPUnit_Framework_TestCase
{
    private $parser;

    private function getPhpParser() : Parser
    {
        if (isset($this->parser)) {
            return $this->parser;
        }

        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        return $this->parser;
    }

    public function testExceptionThrownWhenNonObjectGiven() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        ReflectionObject::createFromInstance(123);
    }

    public function testReflectionWorksWithInternalClasses() : void
    {
        $foo = new \stdClass();

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
        $foo = new ClassForHinting();
        $foo->bar = 'huzzah';

        $classInfo = ReflectionObject::createFromInstance($foo);
        $propInfo = $classInfo->getProperty('bar');

        self::assertInstanceOf(ReflectionProperty::class, $propInfo);
        self::assertSame('bar', $propInfo->getName());
        self::assertFalse($propInfo->isDefault());
    }

    public function testExceptionThrownWhenInvalidInstanceGiven() : void
    {
        $foo = new ClassForHinting();
        $foo->bar = 'huzzah';

        $classInfo = ReflectionObject::createFromInstance($foo);

        $mockClass = $this->createMock(ReflectionClass::class);

        $reflectionObjectReflection = new \ReflectionObject($classInfo);

        $reflectionObjectObjectReflection = $reflectionObjectReflection->getProperty('object');
        $reflectionObjectObjectReflection->setAccessible(true);
        $reflectionObjectObjectReflection->setValue($classInfo, new \stdClass());

        $reflectionObjectReflectionClassReflection = $reflectionObjectReflection->getProperty('reflectionClass');
        $reflectionObjectReflectionClassReflection->setAccessible(true);
        $reflectionObjectReflectionClassReflection->setValue($classInfo, $mockClass);

        $this->expectException(\InvalidArgumentException::class);
        $classInfo->getProperties();
    }

    public function testGetRuntimePropertiesWithFilter() : void
    {
        $foo = new \stdClass();
        $foo->bar = 'huzzah';

        $classInfo = ReflectionObject::createFromInstance($foo);

        self::assertEmpty($classInfo->getProperties(\ReflectionProperty::IS_STATIC));
        self::assertCount(1, $classInfo->getProperties(\ReflectionProperty::IS_PUBLIC));
        self::assertEmpty($classInfo->getProperties(\ReflectionProperty::IS_PROTECTED));
        self::assertEmpty($classInfo->getProperties(\ReflectionProperty::IS_PRIVATE));
    }

    public function testGetRuntimeImmediatePropertiesWithFilter() : void
    {
        $foo = new \stdClass();
        $foo->bar = 'huzzah';

        $classInfo = ReflectionObject::createFromInstance($foo);

        self::assertEmpty($classInfo->getImmediateProperties(\ReflectionProperty::IS_STATIC));
        self::assertCount(1, $classInfo->getImmediateProperties(\ReflectionProperty::IS_PUBLIC));
        self::assertEmpty($classInfo->getImmediateProperties(\ReflectionProperty::IS_PROTECTED));
        self::assertEmpty($classInfo->getImmediateProperties(\ReflectionProperty::IS_PRIVATE));
    }

    /**
     * This data provider gets all the public methods from ReflectionClass, but
     * filters out a few methods we want to test manually
     *
     * @return array
     */
    public function reflectionClassMethodProvider() : array
    {
        $publicClassMethods = get_class_methods(ReflectionClass::class);

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
            if (!in_array($method, $ignoreMethods, true)) {
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
        $publicObjectMethods = get_class_methods(ReflectionObject::class);
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
        $mockReflectionClassReflection = new \ReflectionClass(ReflectionClass::class);

        $php = '<?php class stdClass {}';

        $mockReflectionClassNodeReflection = $mockReflectionClassReflection->getProperty('locatedSource');
        $mockReflectionClassNodeReflection->setAccessible(true);
        $mockReflectionClassNodeReflection->setValue($mockReflectionClass, new EvaledLocatedSource($php));

        $mockReflectionClassNodeReflection = $mockReflectionClassReflection->getProperty('node');
        $mockReflectionClassNodeReflection->setAccessible(true);
        $mockReflectionClassNodeReflection->setValue($mockReflectionClass, $this->getPhpParser()->parse($php)[0]);

        // Create the ReflectionObject from a dummy class
        $reflectionObject = ReflectionObject::createFromInstance(new \stdClass());

        // Override the reflectionClass property on the ReflectionObject to use
        // the mocked reflectionclass above
        $reflectionObjectReflection = new \ReflectionObject($reflectionObject);
        $reflectionObjectReflectionClassPropertyReflection = $reflectionObjectReflection->getProperty('reflectionClass');
        $reflectionObjectReflectionClassPropertyReflection->setAccessible(true);
        $reflectionObjectReflectionClassPropertyReflection->setValue($reflectionObject, $mockReflectionClass);

        $reflectionObjectReflectionMethod = $reflectionObjectReflection->getMethod($methodName);
        $fakeParams = array_map(
            function (\ReflectionParameter $parameter) {
                switch((string)$parameter->getType()) {
                    case 'int':
                        return random_int(1, 1000);
                    case 'null':
                        return null;
                    case 'bool':
                        return (bool)random_int(0, 1);
                    default:
                        return uniqid('stringParam', true);
                }
            },
            $reflectionObjectReflectionMethod->getParameters()
        );

        // Finally, call the method name with some dummy parameters. This should
        // ensure that the method of the same name gets called on the
        // $mockReflectionClass mock (as we expect $methodName to be called)
        $reflectionObject->{$methodName}(...$fakeParams);
    }

    public function testCreateFromNodeThrowsException() : void
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mReflector */
        $mReflector = $this->createMock(Reflector::class);

        /** @var ClassLike|\PHPUnit_Framework_MockObject_MockObject $mClassNode */
        $mClassNode = $this->createMock(ClassLike::class);

        /** @var LocatedSource|\PHPUnit_Framework_MockObject_MockObject $mLocatedSource */
        $mLocatedSource = $this->createMock(LocatedSource::class);

        $this->expectException(\LogicException::class);
        ReflectionObject::createFromNode($mReflector, $mClassNode, $mLocatedSource);
    }

    public function testCreateFromNameThrowsException() : void
    {
        $this->expectException(\LogicException::class);
        ReflectionObject::createFromName('foo');
    }

    public function testReflectionObjectExportMatchesExpectation() : void
    {
        $foo = new ClassForHinting();
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
        $actualExport = ReflectionObject::export($foo);

        self::assertStringMatchesFormat($expectedExport, $actualExport);
    }

    public function testCannotClone() : void
    {
        $classInfo = ReflectionObject::createFromInstance(new \stdClass());

        $this->expectException(Uncloneable::class);
        $unused = clone $classInfo;
    }
}
