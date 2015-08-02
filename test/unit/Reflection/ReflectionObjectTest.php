<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionObject;
use BetterReflection\Reflection\ReflectionProperty;
use BetterReflection\SourceLocator\EvaledLocatedSource;
use BetterReflection\SourceLocator\LocatedSource;
use BetterReflectionTest\Fixture\ClassForHinting;
use PhpParser\Lexer;
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
     * @param string $methodName
     * @dataProvider reflectionClassMethodProvider
     */
    public function testReflectionObjectOverridesAllMethodsInReflectionClass($methodName)
    {
        $publicObjectMethods = get_class_methods(ReflectionObject::class);

        $this->assertContains($methodName, $publicObjectMethods);

        $mockReflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->setMethods([$methodName])
            ->getMock();
        $mockReflectionClass
            ->expects($this->atLeastOnce())
            ->method($methodName);

        $mockReflectionClassReflection = new \ReflectionClass(ReflectionClass::class);

        $php = '<?php class stdClass {}';

        $mockReflectionClassNodeReflection = $mockReflectionClassReflection->getProperty('locatedSource');
        $mockReflectionClassNodeReflection->setAccessible(true);
        $mockReflectionClassNodeReflection->setValue($mockReflectionClass, new EvaledLocatedSource($php));

        $mockReflectionClassNodeReflection = $mockReflectionClassReflection->getProperty('node');
        $mockReflectionClassNodeReflection->setAccessible(true);
        $mockReflectionClassNodeReflection->setValue($mockReflectionClass, $this->getPhpParser()->parse($php)[0]);

        $reflectionObject = ReflectionObject::createFromInstance(new \stdClass());

        $reflectionObjectReflection = new \ReflectionObject($reflectionObject);
        $reflectionObjectReflectionClassPropertyReflection = $reflectionObjectReflection->getProperty('reflectionClass');
        $reflectionObjectReflectionClassPropertyReflection->setAccessible(true);
        $reflectionObjectReflectionClassPropertyReflection->setValue($reflectionObject, $mockReflectionClass);

        $reflectionObject->{$methodName}('foo', 'bar', 'baz');
    }

    public function testCreateFromNodeThrowsException()
    {
        $this->setExpectedException(\LogicException::class);
        ReflectionObject::createFromNode();
    }

    public function testCreateFromNameThrowsException()
    {
        $this->setExpectedException(\LogicException::class);
        ReflectionObject::createFromName();
    }
}
