<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Autoload;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\ClassLoader;
use Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\LoaderMethodInterface;
use Roave\BetterReflection\Util\Autoload\ClassPrinter\PhpParserPrinter;
use Roave\BetterReflection\Util\Autoload\Exception\ClassAlreadyLoaded;
use Roave\BetterReflection\Util\Autoload\Exception\ClassAlreadyRegistered;
use Roave\BetterReflection\Util\Autoload\Exception\FailedToLoadClass;
use Roave\BetterReflectionTest\Fixture\AnotherTestClassForAutoloader;
use Roave\BetterReflectionTest\Fixture\ClassLoaderShouldNotBeLoaded;
use Roave\BetterReflectionTest\Fixture\JustAnotherTestClassForAutoloader;
use Roave\BetterReflectionTest\Fixture\TestClassForAutoloader;
use stdClass;

use function class_exists;
use function count;
use function spl_autoload_functions;
use function spl_autoload_unregister;

/**
 * @covers \Roave\BetterReflection\Util\Autoload\ClassLoader
 */
final class ClassLoaderTest extends TestCase
{
    public function testAutoloadSelfRegisters(): void
    {
        $initialAutoloaderCount = count(spl_autoload_functions());

        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loader       = new ClassLoader($loaderMethod);

        self::assertCount($initialAutoloaderCount + 1, spl_autoload_functions());

        spl_autoload_unregister($loader);

        self::assertCount($initialAutoloaderCount, spl_autoload_functions());
    }

    public function testAutoloadTriggersLoaderMethod(): void
    {
        $reflection = ReflectionClass::createFromName(TestClassForAutoloader::class);
        self::assertFalse(class_exists(TestClassForAutoloader::class, false));

        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loaderMethod->expects(self::once())
            ->method('__invoke')
            ->with($reflection)
            ->willReturnCallback(static function () use ($reflection): void {
                eval((new PhpParserPrinter())->__invoke($reflection));
            });

        $loader = new ClassLoader($loaderMethod);
        $loader->addClass($reflection);

        new TestClassForAutoloader();

        spl_autoload_unregister($loader);
    }

    public function testAutoloaderTriggersLoaderMethodOnlyOnce(): void
    {
        $reflection = ReflectionClass::createFromName(ClassLoaderShouldNotBeLoaded::class);

        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loaderMethod->expects(self::once())
            ->method('__invoke');

        $loader = new ClassLoader($loaderMethod);
        $loader->addClass($reflection);

        self::expectException(FailedToLoadClass::class);
        $loader->__invoke(ClassLoaderShouldNotBeLoaded::class);
    }

    public function testInvokeReturnsFalseForUnknownClass(): void
    {
        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loader       = new ClassLoader($loaderMethod);

        self::assertFalse($loader->__invoke(AnotherTestClassForAutoloader::class));

        spl_autoload_unregister($loader);
    }

    public function testInvokeReturnsTrueForLoadedClass(): void
    {
        $reflection = ReflectionClass::createFromName(JustAnotherTestClassForAutoloader::class);

        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loaderMethod->expects(self::once())
            ->method('__invoke')
            ->with($reflection)
            ->willReturnCallback(static function () use ($reflection): void {
                eval((new PhpParserPrinter())->__invoke($reflection));
            });

        $loader = new ClassLoader($loaderMethod);
        $loader->addClass($reflection);

        self::assertTrue($loader->__invoke(JustAnotherTestClassForAutoloader::class));

        spl_autoload_unregister($loader);
    }

    public function testAddClassThrowsExceptionWhenClassAlreadyRegisteredInAutoload(): void
    {
        $reflection = ReflectionClass::createFromName(AnotherTestClassForAutoloader::class);

        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loader       = new ClassLoader($loaderMethod);

        $loader->addClass($reflection);

        $this->expectException(ClassAlreadyRegistered::class);
        $loader->addClass($reflection);
    }

    public function testAddClassThrowsExceptionWhenClassAlreadyLoaded(): void
    {
        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loader       = new ClassLoader($loaderMethod);

        $this->expectException(ClassAlreadyLoaded::class);
        $loader->addClass(ReflectionClass::createFromName(stdClass::class));
    }

    /**
     * @todo I'd like to figure out a better way of doing this; weird interactions with other tests here, but it works
     * @runInSeparateProcess
     */
    public function testAutoloadThrowsExceptionWhenClassIsNotLoadedCorrectlyAfterAttemptingToLoad(): void
    {
        $reflection = ReflectionClass::createFromName(AnotherTestClassForAutoloader::class);
        self::assertFalse(class_exists(AnotherTestClassForAutoloader::class, false));

        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loaderMethod->expects(self::once())
            ->method('__invoke')
            ->with($reflection);

        $loader = new ClassLoader($loaderMethod);
        $loader->addClass($reflection);

        $this->expectException(FailedToLoadClass::class);
        new AnotherTestClassForAutoloader();
    }
}
