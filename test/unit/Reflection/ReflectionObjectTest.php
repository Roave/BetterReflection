<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionObject;
use BetterReflection\Reflection\ReflectionProperty;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\EvaledLocatedSource;
use BetterReflection\SourceLocator\LocatedSource;
use BetterReflectionTest\Fixture\ClassForHinting;
use PhpParser\Lexer;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Parser;

/**
 * @covers \BetterReflection\Reflection\ReflectionObject
 */
class ReflectionObjectTest extends \PHPUnit_Framework_TestCase
{
    private $parser;

    /**
     * @return Parser
     */
    private function getPhpParser()
    {
        if (isset($this->parser)) {
            return $this->parser;
        }

        $this->parser = new Parser\Multiple([
            new Parser\Php7(new Lexer()),
            new Parser\Php5(new Lexer())
        ]);
        return $this->parser;
    }

    public function testExceptionThrownWhenNonObjectGiven()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        ReflectionObject::createFromInstance(123);
    }

    public function testReflectionWorksWithInternalClasses()
    {
        $foo = new \stdClass();

        $classInfo = ReflectionObject::createFromInstance($foo);
        $this->assertInstanceOf(ReflectionObject::class, $classInfo);
        $this->assertSame('stdClass', $classInfo->getName());
        $this->assertTrue($classInfo->isInternal());
    }

    public function testReflectionWorksWithEvaledClasses()
    {
        $foo = new ClassForHinting();

        $classInfo = ReflectionObject::createFromInstance($foo);
        $this->assertInstanceOf(ReflectionObject::class, $classInfo);
        $this->assertSame(ClassForHinting::class, $classInfo->getName());
        $this->assertFalse($classInfo->isInternal());
    }

    public function testReflectionWorksWithDynamicallyDeclaredMembers()
    {
        $foo = new ClassForHinting();
        $foo->bar = 'huzzah';

        $classInfo = ReflectionObject::createFromInstance($foo);
        $propInfo = $classInfo->getProperty('bar');

        $this->assertInstanceOf(ReflectionProperty::class, $propInfo);
        $this->assertSame('bar', $propInfo->getName());
        $this->assertFalse($propInfo->isDefault());
    }

    public function testExceptionThrownWhenInvalidInstanceGiven()
    {
        $foo = new ClassForHinting();
        $foo->bar = 'huzzah';

        $classInfo = ReflectionObject::createFromInstance($foo);

        $mockClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionObjectReflection = new \ReflectionObject($classInfo);

        $reflectionObjectObjectReflection = $reflectionObjectReflection->getProperty('object');
        $reflectionObjectObjectReflection->setAccessible(true);
        $reflectionObjectObjectReflection->setValue($classInfo, new \stdClass());

        $reflectionObjectReflectionClassReflection = $reflectionObjectReflection->getProperty('reflectionClass');
        $reflectionObjectReflectionClassReflection->setAccessible(true);
        $reflectionObjectReflectionClassReflection->setValue($classInfo, $mockClass);

        $this->setExpectedException(\InvalidArgumentException::class);
        $classInfo->getProperties();
    }

    /**
     * This data provider gets all the public methods from ReflectionClass, but
     * filters out a few methods we want to test manually
     *
     * @return array
     */
    public function reflectionClassMethodProvider()
    {
        $publicClassMethods = get_class_methods(ReflectionClass::class);

        $ignoreMethods = [
            'createFromName',
            'createFromNode',
            '__toString',
            'export',
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
    public function testReflectionObjectOverridesAllMethodsInReflectionClass($methodName)
    {
        // First, ensure the expected method even exists
        $publicObjectMethods = get_class_methods(ReflectionObject::class);
        $this->assertContains($methodName, $publicObjectMethods);

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

        // Finally, call the method name with some dummy parameters. This should
        // ensure that the method of the same name gets called on the
        // $mockReflectionClass mock (as we expect $methodName to be called)
        $reflectionObject->{$methodName}('foo', 'bar', 'baz');
    }

    public function testCreateFromNodeThrowsException()
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mReflector */
        $mReflector = $this->getMockBuilder(Reflector::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ClassLike|\PHPUnit_Framework_MockObject_MockObject $mClassNode */
        $mClassNode = $this->getMockBuilder(ClassLike::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var LocatedSource|\PHPUnit_Framework_MockObject_MockObject $mLocatedSource */
        $mLocatedSource = $this->getMockBuilder(LocatedSource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setExpectedException(\LogicException::class);
        ReflectionObject::createFromNode($mReflector, $mClassNode, $mLocatedSource);
    }

    public function testCreateFromNameThrowsException()
    {
        $this->setExpectedException(\LogicException::class);
        ReflectionObject::createFromName('foo');
    }

    public function testReflectionObjectExportMatchesExpectation()
    {
        $foo = new ClassForHinting();
        $foo->bar = 'huzzah';

        $expectedExport = <<<'BLAH'
Object of class [ <user> class BetterReflectionTest\Fixture\ClassForHinting ] {
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

        $this->assertStringMatchesFormat($expectedExport, $actualExport);
    }
}
