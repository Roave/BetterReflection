<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Reflector;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Reflection\ReflectionFunction;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Rector\BetterReflection\Reflector\FunctionReflector;
use Rector\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Located\LocatedSource;
use Rector\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Rector\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Rector\BetterReflection\SourceLocator\Ast\Locator
 */
class LocatorTest extends TestCase
{
    /**
     * @var Locator
     */
    private $locator;

    protected function setUp() : void
    {
        parent::setUp();

        $this->locator = new Locator(BetterReflectionSingleton::instance()->phpParser());
    }

    private function getIdentifier(string $name, string $type) : Identifier
    {
        return new Identifier($name, new IdentifierType($type));
    }

    public function testReflectingWithinNamespace() : void
    {
        $php = '<?php
        namespace Foo;
        class Bar {}
        ';

        $classInfo = $this->locator->findReflection(
            new ClassReflector(new StringSourceLocator($php, $this->locator)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo\Bar', IdentifierType::IDENTIFIER_CLASS)
        );

        self::assertInstanceOf(ReflectionClass::class, $classInfo);
    }

    public function testReflectingTopLevelClass() : void
    {
        $php = '<?php
        class Foo {}
        ';

        $classInfo = $this->locator->findReflection(
            new ClassReflector(new StringSourceLocator($php, $this->locator)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS)
        );

        self::assertInstanceOf(ReflectionClass::class, $classInfo);
    }

    public function testReflectingTopLevelFunction() : void
    {
        $php = '<?php
        function foo() {}
        ';

        $functionInfo = $this->locator->findReflection(
            new FunctionReflector(new StringSourceLocator($php, $this->locator), BetterReflectionSingleton::instance()->classReflector()),
            new LocatedSource($php, null),
            $this->getIdentifier('foo', IdentifierType::IDENTIFIER_FUNCTION)
        );

        self::assertInstanceOf(ReflectionFunction::class, $functionInfo);
    }

    public function testReflectThrowsExeptionWhenClassNotFoundAndNoNodesExist() : void
    {
        $php = '<?php';

        $this->expectException(IdentifierNotFound::class);
        $this->locator->findReflection(
            new ClassReflector(new StringSourceLocator($php, $this->locator)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS)
        );
    }

    public function testReflectThrowsExeptionWhenClassNotFoundButNodesExist() : void
    {
        $php = "<?php
        namespace Foo;
        echo 'Hello world';
        ";

        $this->expectException(IdentifierNotFound::class);
        $this->locator->findReflection(
            new ClassReflector(new StringSourceLocator($php, $this->locator)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS)
        );
    }

    public function testFindReflectionsOfTypeThrowsParseToAstFailureExceptionWithInvalidCode() : void
    {
        $phpCode = '<?php syntax error';

        $identifierType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);
        $sourceLocator  = new StringSourceLocator($phpCode, $this->locator);
        $reflector      = new ClassReflector($sourceLocator);

        $locatedSource = new LocatedSource($phpCode, null);

        $this->expectException(ParseToAstFailure::class);
        $this->locator->findReflectionsOfType($reflector, $locatedSource, $identifierType);
    }
}
