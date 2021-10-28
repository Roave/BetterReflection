<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use Foo\Bar\AutoloadableClassWithTwoDirectories;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionObject;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\AutoloadableAlias;
use Roave\BetterReflectionTest\Fixture\AutoloadableByAlias;
use Roave\BetterReflectionTest\Fixture\AutoloadableClassInPhar;
use Roave\BetterReflectionTest\Fixture\AutoloadableEnum;
use Roave\BetterReflectionTest\Fixture\AutoloadableInterface;
use Roave\BetterReflectionTest\Fixture\AutoloadableTrait;
use Roave\BetterReflectionTest\Fixture\BrokenAutoloaderException;
use Roave\BetterReflectionTest\Fixture\ClassForHinting;
use Roave\BetterReflectionTest\Fixture\ClassNotInPhar;
use Roave\BetterReflectionTest\Fixture\ExampleClass;

use function class_exists;
use function enum_exists;
use function file_get_contents;
use function file_put_contents;
use function interface_exists;
use function is_file;
use function restore_error_handler;
use function set_error_handler;
use function spl_autoload_register;
use function spl_autoload_unregister;
use function str_replace;
use function sys_get_temp_dir;
use function tempnam;
use function trait_exists;
use function uniqid;
use function unlink;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator
 */
class AutoloadSourceLocatorTest extends TestCase
{
    private Locator $astLocator;

    private Reflector $reflector;

    protected function setUp(): void
    {
        parent::setUp();

        $configuration    = BetterReflectionSingleton::instance();
        $this->astLocator = $configuration->astLocator();
        $this->reflector  = $configuration->reflector();
    }

    /** @return Reflector&MockObject */
    private function getMockReflector()
    {
        return $this->createMock(Reflector::class);
    }

    public function testClassLoads(): void
    {
        $reflector = new DefaultReflector(new AutoloadSourceLocator($this->astLocator));

        self::assertFalse(class_exists(ExampleClass::class, false));
        $classInfo = $reflector->reflectClass(ExampleClass::class);
        self::assertFalse(class_exists(ExampleClass::class, false));

        self::assertSame('ExampleClass', $classInfo->getShortName());
    }

    public function testClassLoadsWorksWithExistingClass(): void
    {
        $reflector = new DefaultReflector(new AutoloadSourceLocator($this->astLocator));

        // Ensure class is loaded first
        new ClassForHinting();
        self::assertTrue(class_exists(ClassForHinting::class, false));

        $classInfo = $reflector->reflectClass(ClassForHinting::class);

        self::assertSame('ClassForHinting', $classInfo->getShortName());
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanLocateAutoloadableInterface(): void
    {
        self::assertFalse(interface_exists(AutoloadableInterface::class, false));

        self::assertInstanceOf(
            LocatedSource::class,
            (new AutoloadSourceLocator($this->astLocator))
                ->locateIdentifier($this->getMockReflector(), new Identifier(
                    AutoloadableInterface::class,
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                ))->getLocatedSource(),
        );

        self::assertFalse(interface_exists(AutoloadableInterface::class, false));
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanLocateAutoloadedInterface(): void
    {
        self::assertTrue(interface_exists(AutoloadableInterface::class));

        self::assertInstanceOf(
            LocatedSource::class,
            (new AutoloadSourceLocator($this->astLocator))
                ->locateIdentifier($this->getMockReflector(), new Identifier(
                    AutoloadableInterface::class,
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                ))->getLocatedSource(),
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanLocateAutoloadableTrait(): void
    {
        self::assertFalse(trait_exists(AutoloadableTrait::class, false));

        self::assertInstanceOf(
            LocatedSource::class,
            (new AutoloadSourceLocator($this->astLocator))
                ->locateIdentifier($this->getMockReflector(), new Identifier(
                    AutoloadableTrait::class,
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                ))->getLocatedSource(),
        );

        self::assertFalse(trait_exists(AutoloadableTrait::class, false));
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanLocateAutoloadedTrait(): void
    {
        self::assertTrue(trait_exists(AutoloadableTrait::class));

        self::assertInstanceOf(
            LocatedSource::class,
            (new AutoloadSourceLocator($this->astLocator))
                ->locateIdentifier($this->getMockReflector(), new Identifier(
                    AutoloadableTrait::class,
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                ))->getLocatedSource(),
        );
    }

    /**
     * @runInSeparateProcess
     * @requires PHP >= 8.1
     */
    public function testCanLocateAutoloadableEnum(): void
    {
        self::assertFalse(enum_exists(AutoloadableEnum::class, false));

        self::assertInstanceOf(
            LocatedSource::class,
            (new AutoloadSourceLocator($this->astLocator))
                ->locateIdentifier($this->getMockReflector(), new Identifier(
                    AutoloadableEnum::class,
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                ))->getLocatedSource(),
        );

        self::assertFalse(enum_exists(AutoloadableEnum::class, false));
    }

    /**
     * @runInSeparateProcess
     * @requires PHP >= 8.1
     */
    public function testCanLocateAutoloadedEnum(): void
    {
        self::assertTrue(enum_exists(AutoloadableEnum::class));

        self::assertInstanceOf(
            LocatedSource::class,
            (new AutoloadSourceLocator($this->astLocator))
                ->locateIdentifier($this->getMockReflector(), new Identifier(
                    AutoloadableEnum::class,
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                ))->getLocatedSource(),
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanLocateAutoloadedClassByAlias(): void
    {
        require __DIR__ . '/../../Fixture/AutoloadableByAlias.php';

        self::assertTrue(class_exists(AutoloadableAlias::class, false));

        $reflection = (new AutoloadSourceLocator($this->astLocator))->locateIdentifier($this->getMockReflector(), new Identifier(
            AutoloadableAlias::class,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
        ));

        self::assertNotNull($reflection);
        self::assertSame(AutoloadableByAlias::class, $reflection->getName());
    }

    public function testFunctionLoads(): void
    {
        $reflector = new DefaultReflector(new AutoloadSourceLocator($this->astLocator));

        require_once __DIR__ . '/../../Fixture/Functions.php';
        $classInfo = $reflector->reflectFunction('Roave\BetterReflectionTest\Fixture\myFunction');

        self::assertSame('myFunction', $classInfo->getShortName());
    }

    public function testFunctionReflectionFailsWhenFunctionNotDefined(): void
    {
        $reflector = new DefaultReflector(new AutoloadSourceLocator($this->astLocator));

        $this->expectException(IdentifierNotFound::class);
        $reflector->reflectFunction('this function does not exist, hopefully');
    }

    public function testConstantLoadsByConst(): void
    {
        $reflector = new DefaultReflector(new AutoloadSourceLocator($this->astLocator));

        require_once __DIR__ . '/../../Fixture/Constants.php';
        $reflection = $reflector->reflectConstant('Roave\BetterReflectionTest\Fixture\BY_CONST_2');

        self::assertSame('Roave\BetterReflectionTest\Fixture\BY_CONST_2', $reflection->getName());
        self::assertSame('BY_CONST_2', $reflection->getShortName());
    }

    /**
     * Running in a separate process to reduce the amount of existing files to scan in case of a constant lookup failure
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAutoloadSourceLocatorWillNotFindConstantDeclarationFileIfDeclarationFileIsRemoved(): void
    {
        $constantName        = str_replace('.', '', uniqid('constant_name', true));
        $temporarySourceFile = tempnam(sys_get_temp_dir(), 'AutoloadSourceLocatorTest');

        self::assertIsString($temporarySourceFile);

        file_put_contents(
            $temporarySourceFile,
            '<?php namespace Roave\BetterReflectionTest\SourceLocator\Type; const ' . $constantName . ' = "foo";',
        );

        require $temporarySourceFile;

        $sourceLocator = new AutoloadSourceLocator($this->astLocator);
        $reflector     = new DefaultReflector($sourceLocator);

        self::assertSame(
            'Roave\BetterReflectionTest\SourceLocator\Type\\' . $constantName,
            $reflector->reflectConstant('Roave\BetterReflectionTest\SourceLocator\Type\\' . $constantName)
                ->getName(),
        );

        unlink($temporarySourceFile);

        $sourceLocator   = new AutoloadSourceLocator($this->astLocator);
        $secondReflector = new DefaultReflector($sourceLocator);

        $this->expectException(IdentifierNotFound::class);

        $secondReflector->reflectConstant('Roave\BetterReflectionTest\SourceLocator\Type\\' . $constantName);
    }

    public function testConstantLoadsByDefine(): void
    {
        $reflector = new DefaultReflector(new AutoloadSourceLocator($this->astLocator));

        require_once __DIR__ . '/../../Fixture/Constants.php';
        $reflection = $reflector->reflectConstant('BY_DEFINE');

        self::assertSame('BY_DEFINE', $reflection->getName());
        self::assertSame('BY_DEFINE', $reflection->getShortName());
    }

    public function testConstantLoadsByDefineWithNamespace(): void
    {
        $reflector = new DefaultReflector(new AutoloadSourceLocator($this->astLocator));

        require_once __DIR__ . '/../../Fixture/Constants.php';
        $reflection = $reflector->reflectConstant('Roave\BetterReflectionTest\Fixture\BY_DEFINE');

        self::assertSame('Roave\BetterReflectionTest\Fixture\BY_DEFINE', $reflection->getName());
        self::assertSame('BY_DEFINE', $reflection->getShortName());
    }

    public function testInternalClassDoesNotLoad(): void
    {
        $reflector = new DefaultReflector(new AutoloadSourceLocator($this->astLocator));

        $this->expectException(IdentifierNotFound::class);
        $reflector->reflectClass(ReflectionClass::class);
    }

    public function testInternalConstantDoesNotLoad(): void
    {
        $reflector = new DefaultReflector(new AutoloadSourceLocator($this->astLocator));

        $this->expectException(IdentifierNotFound::class);
        $reflector->reflectConstant('E_ALL');
    }

    public function testConstantReflectionFailsWhenConstantNotDefined(): void
    {
        $reflector = new DefaultReflector(new AutoloadSourceLocator($this->astLocator));

        $this->expectException(IdentifierNotFound::class);
        $reflector->reflectConstant('this constant does not exist, hopefully');
    }

    public function testNullReturnedWhenInvalidTypeGiven(): void
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

    public function testReturnsNullWhenUnableToAutoload(): void
    {
        $sourceLocator = new AutoloadSourceLocator($this->astLocator);

        self::assertNull($sourceLocator->locateIdentifier(
            new DefaultReflector($sourceLocator),
            new Identifier('Some\Class\That\Cannot\Exist', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
        ));
    }

    public function testShouldNotConsiderEvaledSources(): void
    {
        $className = uniqid('generatedClassName', false);

        eval('class ' . $className . '{}');

        self::assertNull(
            (new AutoloadSourceLocator($this->astLocator))
                ->locateIdentifier($this->getMockReflector(), new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))),
        );
    }

    public function testReturnsNullWithInternalFunctions(): void
    {
        self::assertNull(
            (new AutoloadSourceLocator($this->astLocator))
                ->locateIdentifier(
                    $this->getMockReflector(),
                    new Identifier('strlen', new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)),
                ),
        );
    }

    public function testCanAutoloadPsr4ClassesInPotentiallyMultipleDirectories(): void
    {
        spl_autoload_register([$this, 'autoload']);

        self::assertNotNull(
            (new AutoloadSourceLocator($this->astLocator))
                ->locateIdentifier(
                    $this->getMockReflector(),
                    new Identifier(AutoloadableClassWithTwoDirectories::class, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                ),
        );

        spl_autoload_unregister([$this, 'autoload']);

        self::assertFalse(class_exists(AutoloadableClassWithTwoDirectories::class, false));
    }

    /**
     * A test autoloader that simulates Composer PSR-4 autoloader with 2 possible directories for the same namespace.
     */
    public function autoload(string $className): bool
    {
        if ($className !== AutoloadableClassWithTwoDirectories::class) {
            return false;
        }

        self::assertFalse(is_file(__DIR__ . '/AutoloadableClassWithTwoDirectories.php'));
        self::assertTrue(is_file(__DIR__ . '/../../Fixture/AutoloadableClassWithTwoDirectories.php'));

        include __DIR__ . '/../../Fixture/AutoloadableClassWithTwoDirectories.php';

        return true;
    }

    /**
     * @runInSeparateProcess
     */
    public function testWillLocateSourcesInPharPath(): void
    {
        require_once 'phar://' . __DIR__ . '/../../Fixture/autoload.phar/vendor/autoload.php';
        spl_autoload_register(static function (string $class): void {
            if ($class !== ClassNotInPhar::class) {
                return;
            }

            include_once __DIR__ . '/../../Fixture/ClassNotInPhar.php';
        });

        $sourceLocator = new AutoloadSourceLocator($this->astLocator);
        $reflector     = new DefaultReflector($sourceLocator);

        $reflection = $reflector->reflectClass(AutoloadableClassInPhar::class);

        $this->assertSame(AutoloadableClassInPhar::class, $reflection->getName());
    }

    public function testBrokenAutoloader(): void
    {
        $getErrorHandler = static function (): ?callable {
            $errorHandler = set_error_handler(static fn (): bool => true);
            restore_error_handler();

            return $errorHandler;
        };

        $toBeThrown           = new BrokenAutoloaderException();
        $brokenAutoloader     = static function () use ($toBeThrown): void {
            throw $toBeThrown;
        };
        $previousErrorHandler = $getErrorHandler();

        spl_autoload_register($brokenAutoloader);

        try {
            (new AutoloadSourceLocator($this->astLocator))
                ->locateIdentifier(
                    $this->getMockReflector(),
                    new Identifier('Whatever', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                );

            self::fail('No exception was thrown');
        } catch (BrokenAutoloaderException $e) {
            self::assertSame($e, $toBeThrown);
        } finally {
            spl_autoload_unregister($brokenAutoloader);
        }

        self::assertSame($previousErrorHandler, $getErrorHandler());
        self::assertNotFalse(file_get_contents(__FILE__));
    }
}
