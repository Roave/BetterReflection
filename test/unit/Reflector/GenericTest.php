<?php

namespace BetterReflectionTest\Reflector;

use BetterReflection\Reflector\Exception\IdentifierNotFound;
use BetterReflection\Reflector\Generic;
use BetterReflection\SourceLocator\AggregateSourceLocator;
use BetterReflection\SourceLocator\StringSourceLocator;
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

        $reflector = new Generic(new StringSourceLocator($php));
        $classInfo = $reflector->reflect($this->getIdentifier('Foo\Bar', IdentifierType::IDENTIFIER_CLASS));

        $this->assertInstanceOf(ReflectionClass::class, $classInfo);
    }

    public function testReflectingTopLevelClass()
    {
        $php = '<?php
        class Foo {}
        ';

        $reflector = new Generic(new StringSourceLocator($php));
        $classInfo = $reflector->reflect($this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS));

        $this->assertInstanceOf(ReflectionClass::class, $classInfo);
    }

    public function testReflectingTopLevelFunction()
    {
        $php = '<?php
        function foo() {}
        ';

        $reflector = new Generic(new StringSourceLocator($php));
        $functionInfo = $reflector->reflect($this->getIdentifier('foo', IdentifierType::IDENTIFIER_FUNCTION));

        $this->assertInstanceOf(ReflectionFunction::class, $functionInfo);
    }

    public function testReflectThrowsExeptionWhenClassNotFoundAndNoNodesExist()
    {
        $php = '<?php';

        $reflector = new Generic(new StringSourceLocator($php));

        $this->setExpectedException(IdentifierNotFound::class);
        $reflector->reflect($this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS));
    }

    public function testReflectThrowsExeptionWhenClassNotFoundButNodesExist()
    {
        $php = "<?php
        namespace Foo;
        echo 'Hello world';
        ";

        $reflector = new Generic(new StringSourceLocator($php));

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

        $reflector = new Generic(new StringSourceLocator($php));

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

        $reflector = new Generic(new StringSourceLocator($php));

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

        $reflector = new Generic(new StringSourceLocator($php));

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

        $reflector = new Generic(new StringSourceLocator($php));

        $found = $reflector->getAllByIdentifierType(new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $this->assertInternalType('array', $found);
        $this->assertCount(0, $found);
    }

    public function testReflectWithAggregateSourceLocatorWhenIdentifierDoesNotExist()
    {
        $reflector = new Generic(new AggregateSourceLocator([
            new StringSourceLocator('<?php'),
            new StringSourceLocator('<?php'),
        ]));

        $this->setExpectedException(IdentifierNotFound::class);
        $reflector->reflect($this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS));
    }

    public function testReflectWithAggregateSourceLocatorFindsClass()
    {
        $reflector = new Generic(new AggregateSourceLocator([
            new StringSourceLocator('<?php'),
            new StringSourceLocator('<?php class Foo {}'),
        ]));

        $classInfo = $reflector->reflect($this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS));

        $this->assertInstanceOf(ReflectionClass::class, $classInfo);
        $this->assertSame('Foo', $classInfo->getName());
    }
}
