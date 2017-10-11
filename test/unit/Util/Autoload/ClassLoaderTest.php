<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Util\Autoload;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Util\Autoload\ClassLoader;
use Rector\BetterReflection\Util\Autoload\ClassLoaderMethod\LoaderMethodInterface;
use Rector\BetterReflection\Util\Autoload\ClassPrinter\PhpParserPrinter;
use Rector\BetterReflection\Util\Autoload\Exception\ClassAlreadyLoaded;
use Rector\BetterReflection\Util\Autoload\Exception\ClassAlreadyRegistered;
use Rector\BetterReflection\Util\Autoload\Exception\FailedToLoadClass;
use Rector\BetterReflectionTest\Fixture\AnotherTestClassForAutoloader;
use Rector\BetterReflectionTest\Fixture\TestClassForAutoloader;
use stdClass;

/**
 * @covers \Rector\BetterReflection\Util\Autoload\ClassLoader
 */
final class ClassLoaderTest extends TestCase
{
    public function testAutoloadSelfRegisters() : void
    {
        $initialAutoloaderCount = \count(\spl_autoload_functions());

        /** @var LoaderMethodInterface|\PHPUnit_Framework_MockObject_MockObject $loaderMethod */
        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loader       = new ClassLoader($loaderMethod);

        self::assertCount($initialAutoloaderCount + 1, \spl_autoload_functions());

        \spl_autoload_unregister($loader);

        self::assertCount($initialAutoloaderCount, \spl_autoload_functions());
    }

    public function testAutoloadTriggersLoaderMethod() : void
    {
        $reflection = ReflectionClass::createFromName(TestClassForAutoloader::class);
        self::assertFalse(\class_exists(TestClassForAutoloader::class, false));

        /** @var LoaderMethodInterface|\PHPUnit_Framework_MockObject_MockObject $loaderMethod */
        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loaderMethod->expects(self::once())
            ->method('__invoke')
            ->with($reflection)
            ->willReturnCallback(function () use ($reflection) : void {
                eval((new PhpParserPrinter())->__invoke($reflection));
            });

        $loader = new ClassLoader($loaderMethod);
        $loader->addClass($reflection);

        new TestClassForAutoloader();

        \spl_autoload_unregister($loader);
    }

    public function testAddClassThrowsExceptionWhenClassAlreadyRegisteredInAutoload() : void
    {
        $reflection = ReflectionClass::createFromName(AnotherTestClassForAutoloader::class);

        /** @var LoaderMethodInterface|\PHPUnit_Framework_MockObject_MockObject $loaderMethod */
        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loader       = new ClassLoader($loaderMethod);

        $loader->addClass($reflection);

        $this->expectException(ClassAlreadyRegistered::class);
        $loader->addClass($reflection);

        \spl_autoload_unregister($loader);
    }

    public function testAddClassThrowsExceptionWhenClassAlreadyLoaded() : void
    {
        /** @var LoaderMethodInterface|\PHPUnit_Framework_MockObject_MockObject $loaderMethod */
        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loader       = new ClassLoader($loaderMethod);

        $this->expectException(ClassAlreadyLoaded::class);
        $loader->addClass(ReflectionClass::createFromName(stdClass::class));

        \spl_autoload_unregister($loader);
    }

    /**
     * @todo I'd like to figure out a better way of doing this; weird interactions with other tests here, but it works
     * @runInSeparateProcess
     */
    public function testAutoloadThrowsExceptionWhenClassIsNotLoadedCorrectlyAfterAttemptingToLoad() : void
    {
        $reflection = ReflectionClass::createFromName(AnotherTestClassForAutoloader::class);
        self::assertFalse(\class_exists(AnotherTestClassForAutoloader::class, false));

        /** @var LoaderMethodInterface|\PHPUnit_Framework_MockObject_MockObject $loaderMethod */
        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loaderMethod->expects(self::once())
            ->method('__invoke')
            ->with($reflection);

        $loader = new ClassLoader($loaderMethod);
        $loader->addClass($reflection);

        $this->expectException(FailedToLoadClass::class);
        new AnotherTestClassForAutoloader();

        \spl_autoload_unregister($loader);
    }
}
