<?php

namespace BetterReflectionTest\Reflector;

use BetterReflection\Reflector\Exception\IdentifierNotFound;
use BetterReflection\Reflector\Generic;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use BetterReflection\SourceLocator\Type\StringSourceLocator;
use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionFunction;

/**
 * @covers \BetterReflection\Reflector\Generic
 */
class GenericTest extends \PHPUnit_Framework_TestCase
{
    private function getIdentifier($name, $type)
    {
        return new Identifier($name, new IdentifierType($type));
    }

    public function testReflectingWithinNamespace()
    {
        $php = '<?php
        namespace Foo;
        class Bar {}
        ';

        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->getMock(Reflector::class);

        $reflector = new Generic(new StringSourceLocator($php), $mockReflector);
        $classInfo = $reflector->reflect($this->getIdentifier('Foo\Bar', IdentifierType::IDENTIFIER_CLASS));

        $this->assertInstanceOf(ReflectionClass::class, $classInfo);
    }

    public function testReflectingTopLevelClass()
    {
        $php = '<?php
        class Foo {}
        ';

        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->getMock(Reflector::class);

        $reflector = new Generic(new StringSourceLocator($php), $mockReflector);
        $classInfo = $reflector->reflect($this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS));

        $this->assertInstanceOf(ReflectionClass::class, $classInfo);
    }

    public function testReflectingTopLevelFunction()
    {
        $php = '<?php
        function foo() {}
        ';

        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->getMock(Reflector::class);

        $reflector = new Generic(new StringSourceLocator($php), $mockReflector);
        $functionInfo = $reflector->reflect($this->getIdentifier('foo', IdentifierType::IDENTIFIER_FUNCTION));

        $this->assertInstanceOf(ReflectionFunction::class, $functionInfo);
    }

    public function testReflectThrowsExeptionWhenClassNotFoundAndNoNodesExist()
    {
        $php = '<?php';

        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->getMock(Reflector::class);

        $reflector = new Generic(new StringSourceLocator($php), $mockReflector);

        $this->setExpectedException(IdentifierNotFound::class);
        $reflector->reflect($this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS));
    }

    public function testReflectThrowsExeptionWhenClassNotFoundButNodesExist()
    {
        $php = "<?php
        namespace Foo;
        echo 'Hello world';
        ";

        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->getMock(Reflector::class);

        $reflector = new Generic(new StringSourceLocator($php), $mockReflector);

        $this->setExpectedException(IdentifierNotFound::class);
        $reflector->reflect($this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS));
    }

    public function testGetAllFunctions()
    {
        $php = '<?php
        namespace Foo;
        function a() {}
        function b() {}
        ';

        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->getMock(Reflector::class);

        $reflector = new Generic(new StringSourceLocator($php), $mockReflector);

        $found = $reflector->getAllByIdentifierType(new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));

        $this->assertInternalType('array', $found);
        $this->assertCount(2, $found);
        $this->assertContainsOnlyInstancesOf(ReflectionFunction::class, $found);
    }

    public function testGetAllFunctionsWhenNoneExist()
    {
        $php = '<?php
        namespace Foo;
        class a {}
        class b {}
        ';

        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->getMock(Reflector::class);

        $reflector = new Generic(new StringSourceLocator($php), $mockReflector);

        $found = $reflector->getAllByIdentifierType(new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));

        $this->assertInternalType('array', $found);
        $this->assertCount(0, $found);
    }

    public function testGetAllClasses()
    {
        $php = '<?php
        namespace Foo;
        class a {}
        class b {}
        ';

        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->getMock(Reflector::class);

        $reflector = new Generic(new StringSourceLocator($php), $mockReflector);

        $found = $reflector->getAllByIdentifierType(new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $this->assertInternalType('array', $found);
        $this->assertCount(2, $found);
        $this->assertContainsOnlyInstancesOf(ReflectionClass::class, $found);
    }

    public function testGetAllClassesWhenNoneExist()
    {
        $php = '<?php
        namespace Foo;
        function a() {}
        function b() {}
        ';

        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->getMock(Reflector::class);

        $reflector = new Generic(new StringSourceLocator($php), $mockReflector);

        $found = $reflector->getAllByIdentifierType(new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $this->assertInternalType('array', $found);
        $this->assertCount(0, $found);
    }

    public function testReflectWithAggregateSourceLocatorWhenIdentifierDoesNotExist()
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->getMock(Reflector::class);

        $reflector = new Generic(new AggregateSourceLocator([
            new StringSourceLocator('<?php'),
            new StringSourceLocator('<?php'),
        ]), $mockReflector);

        $this->setExpectedException(IdentifierNotFound::class);
        $reflector->reflect($this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS));
    }

    public function testReflectWithAggregateSourceLocatorFindsClass()
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $mockReflector */
        $mockReflector = $this->getMock(Reflector::class);

        $reflector = new Generic(new AggregateSourceLocator([
            new StringSourceLocator('<?php'),
            new StringSourceLocator('<?php class Foo {}'),
        ]), $mockReflector);

        $classInfo = $reflector->reflect($this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS));

        $this->assertInstanceOf(ReflectionClass::class, $classInfo);
        $this->assertSame('Foo', $classInfo->getName());
    }
}
