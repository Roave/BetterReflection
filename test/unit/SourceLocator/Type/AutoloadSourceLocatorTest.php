<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\SourceLocator\Type;

use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Rector\BetterReflection\Reflector\FunctionReflector;
use Rector\BetterReflection\Reflector\Reflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Located\LocatedSource;
use Rector\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Rector\BetterReflectionTest\BetterReflectionSingleton;
use Rector\BetterReflectionTest\Fixture\AutoloadableInterface;
use Rector\BetterReflectionTest\Fixture\AutoloadableTrait;
use Rector\BetterReflectionTest\Fixture\ClassForHinting;
use Rector\BetterReflectionTest\Fixture\ExampleClass;

/**
 * @covers \Rector\BetterReflection\SourceLocator\Type\AutoloadSourceLocator
 */
class AutoloadSourceLocatorTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    /**
     * @var ClassReflector
     */
    private $classReflector;

    protected function setUp() : void
    {
        parent::setUp();

        $configuration        = BetterReflectionSingleton::instance();
        $this->astLocator     = $configuration->astLocator();
        $this->classReflector = $configuration->classReflector();
    }

    /**
     * @return Reflector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockReflector()
    {
        return $this->createMock(Reflector::class);
    }

    public function testClassLoads() : void
    {
        $reflector = new ClassReflector(new AutoloadSourceLocator($this->astLocator));

        self::assertFalse(\class_exists(ExampleClass::class, false));
        $classInfo = $reflector->reflect(ExampleClass::class);
        self::assertFalse(\class_exists(ExampleClass::class, false));

        self::assertSame('ExampleClass', $classInfo->getShortName());
    }

    public function testClassLoadsWorksWithExistingClass() : void
    {
        $reflector = new ClassReflector(new AutoloadSourceLocator($this->astLocator));

        // Ensure class is loaded first
        new ClassForHinting();
        self::assertTrue(\class_exists(ClassForHinting::class, false));

        $classInfo = $reflector->reflect(ClassForHinting::class);

        self::assertSame('ClassForHinting', $classInfo->getShortName());
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanLocateAutoloadableInterface() : void
    {
        self::assertFalse(\interface_exists(AutoloadableInterface::class, false));

        self::assertInstanceOf(
            LocatedSource::class,
            (new AutoloadSourceLocator($this->astLocator))
                ->locateIdentifier($this->getMockReflector(), new Identifier(
                    AutoloadableInterface::class,
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
                ))->getLocatedSource()
        );

        self::assertFalse(\interface_exists(AutoloadableInterface::class, false));
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanLocateAutoloadedInterface() : void
    {
        self::assertTrue(\interface_exists(AutoloadableInterface::class));

        self::assertInstanceOf(
            LocatedSource::class,
            (new AutoloadSourceLocator($this->astLocator))
                ->locateIdentifier($this->getMockReflector(), new Identifier(
                    AutoloadableInterface::class,
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
                ))->getLocatedSource()
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanLocateAutoloadableTrait() : void
    {
        self::assertFalse(\trait_exists(AutoloadableTrait::class, false));

        self::assertInstanceOf(
            LocatedSource::class,
            (new AutoloadSourceLocator($this->astLocator))
                ->locateIdentifier($this->getMockReflector(), new Identifier(
                    AutoloadableTrait::class,
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
                ))->getLocatedSource()
        );

        self::assertFalse(\trait_exists(AutoloadableTrait::class, false));
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanLocateAutoloadedTrait() : void
    {
        self::assertTrue(\trait_exists(AutoloadableTrait::class));

        self::assertInstanceOf(
            LocatedSource::class,
            (new AutoloadSourceLocator($this->astLocator))
                ->locateIdentifier($this->getMockReflector(), new Identifier(
                    AutoloadableTrait::class,
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
                ))->getLocatedSource()
        );
    }

    public function testFunctionLoads() : void
    {
        $reflector = new FunctionReflector(new AutoloadSourceLocator($this->astLocator), $this->classReflector);

        require_once __DIR__ . '/../../Fixture/Functions.php';
        $classInfo = $reflector->reflect('Rector\BetterReflectionTest\Fixture\myFunction');

        self::assertSame('myFunction', $classInfo->getShortName());
    }

    public function testFunctionReflectionFailsWhenFunctionNotDefined() : void
    {
        $reflector = new FunctionReflector(new AutoloadSourceLocator($this->astLocator), $this->classReflector);

        $this->expectException(IdentifierNotFound::class);
        $reflector->reflect('this function does not exist, hopefully');
    }

    public function testNullReturnedWhenInvalidTypeGiven() : void
    {
        $locator = new AutoloadSourceLocator($this->astLocator);

        $type           = new IdentifierType();
        $typeReflection = new ReflectionObject($type);
        $prop           = $typeReflection->getProperty('name');
        $prop->setAccessible(true);
        $prop->setValue($type, 'nonsense');

        $identifier = new Identifier('foo', $type);
        self::assertNull($locator->locateIdentifier($this->getMockReflector(), $identifier));
    }

    public function testReturnsNullWhenUnableToAutoload() : void
    {
        $sourceLocator = new AutoloadSourceLocator($this->astLocator);

        self::assertNull($sourceLocator->locateIdentifier(
            new ClassReflector($sourceLocator),
            new Identifier('Some\Class\That\Cannot\Exist', new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        ));
    }

    public function testShouldNotConsiderEvaledSources() : void
    {
        $className = \uniqid('generatedClassName', false);

        eval('class ' . $className . '{}');

        self::assertNull(
            (new AutoloadSourceLocator($this->astLocator))
                ->locateIdentifier($this->getMockReflector(), new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)))
        );
    }

    public function testReturnsNullWithInternalFunctions() : void
    {
        self::assertNull(
            (new AutoloadSourceLocator($this->astLocator))
                ->locateIdentifier(
                    $this->getMockReflector(),
                    new Identifier('strlen', new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))
                )
        );
    }
}
