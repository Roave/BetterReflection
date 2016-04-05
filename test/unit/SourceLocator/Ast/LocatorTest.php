<?php

namespace BetterReflectionTest\Reflector;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionFunction;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\Reflector\Exception\IdentifierNotFound;
use BetterReflection\Reflector\FunctionReflector;
use BetterReflection\SourceLocator\Ast\Exception\AstParse;
use BetterReflection\SourceLocator\Ast\Locator;
use BetterReflection\SourceLocator\Located\LocatedSource;
use BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\Ast\Locator
 */
class LocatorTest extends \PHPUnit_Framework_TestCase
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

        $classInfo = (new Locator())->findReflection(
            new ClassReflector(new StringSourceLocator($php)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo\Bar', IdentifierType::IDENTIFIER_CLASS)
        );

        $this->assertInstanceOf(ReflectionClass::class, $classInfo);
    }

    public function testReflectingTopLevelClass()
    {
        $php = '<?php
        class Foo {}
        ';

        $classInfo = (new Locator())->findReflection(
            new ClassReflector(new StringSourceLocator($php)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS)
        );

        $this->assertInstanceOf(ReflectionClass::class, $classInfo);
    }

    public function testReflectingTopLevelFunction()
    {
        $php = '<?php
        function foo() {}
        ';

        $functionInfo = (new Locator())->findReflection(
            new FunctionReflector(new StringSourceLocator($php)),
            new LocatedSource($php, null),
            $this->getIdentifier('foo', IdentifierType::IDENTIFIER_FUNCTION)
        );

        $this->assertInstanceOf(ReflectionFunction::class, $functionInfo);
    }

    public function testReflectThrowsExeptionWhenClassNotFoundAndNoNodesExist()
    {
        $php = '<?php';

        $this->setExpectedException(IdentifierNotFound::class);
        (new Locator())->findReflection(
            new ClassReflector(new StringSourceLocator($php)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS)
        );
    }

    public function testReflectThrowsExeptionWhenClassNotFoundButNodesExist()
    {
        $php = "<?php
        namespace Foo;
        echo 'Hello world';
        ";

        $this->setExpectedException(IdentifierNotFound::class);
        (new Locator())->findReflection(
            new ClassReflector(new StringSourceLocator($php)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS)
        );
    }

    public function testFindReflectionsOfTypeThrowsAstParseExceptionWithInvalidCode()
    {
        $locator = new Locator();

        $phpCode = '<?php syntax error';

        $identifierType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);
        $sourceLocator = new StringSourceLocator($phpCode);
        $reflector = new ClassReflector($sourceLocator);

        $locatedSource = new LocatedSource($phpCode, null);

        $this->expectException(AstParse::class);
        $locator->findReflectionsOfType($reflector, $locatedSource, $identifierType);
    }
}
