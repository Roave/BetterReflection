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

/** @covers \Roave\BetterReflection\Util\Autoload\ClassLoader */
final class ClassLoaderTest extends TestCase
{
    private ClassLoader|null $loader = null;

    protected function tearDown(): void
    {
        if ($this->loader === null) {
            return;
        }

        spl_autoload_unregister($this->loader);
        $this->loader = null;
    }

    public function testAutoloadSelfRegisters(): void
    {
        $initialAutoloaderCount = count(spl_autoload_functions());

        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $this->loader = new ClassLoader($loaderMethod);

        self::assertCount($initialAutoloaderCount + 1, spl_autoload_functions());

        spl_autoload_unregister($this->loader);

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

        $this->loader = new ClassLoader($loaderMethod);
        $this->loader->addClass($reflection);

        new TestClassForAutoloader();
    }

    public function testAutoloaderTriggersLoaderMethodOnlyOnce(): void
    {
        $reflection = ReflectionClass::createFromName(ClassLoaderShouldNotBeLoaded::class);

        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loaderMethod->expects(self::once())
            ->method('__invoke');

        $this->loader = new ClassLoader($loaderMethod);
        $this->loader->addClass($reflection);

        self::expectException(FailedToLoadClass::class);
        $this->loader->__invoke(ClassLoaderShouldNotBeLoaded::class);
    }

    public function testInvokeReturnsFalseForUnknownClass(): void
    {
        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $this->loader = new ClassLoader($loaderMethod);

        self::assertFalse($this->loader->__invoke(AnotherTestClassForAutoloader::class));
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

        $this->loader = new ClassLoader($loaderMethod);
        $this->loader->addClass($reflection);

        self::assertTrue($this->loader->__invoke(JustAnotherTestClassForAutoloader::class));
    }

    public function testAddClassThrowsExceptionWhenClassAlreadyRegisteredInAutoload(): void
    {
        $reflection = ReflectionClass::createFromName(AnotherTestClassForAutoloader::class);

        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $this->loader = new ClassLoader($loaderMethod);

        $this->loader->addClass($reflection);

        $this->expectException(ClassAlreadyRegistered::class);
        $this->loader->addClass($reflection);
    }

    public function testAddClassThrowsExceptionWhenClassAlreadyLoaded(): void
    {
        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $this->loader = new ClassLoader($loaderMethod);

        $this->expectException(ClassAlreadyLoaded::class);
        $this->loader->addClass(ReflectionClass::createFromName(stdClass::class));
    }

    public function testAutoloadThrowsExceptionWhenClassIsNotLoadedCorrectlyAfterAttemptingToLoad(): void
    {
        $reflection = ReflectionClass::createFromName(AnotherTestClassForAutoloader::class);
        self::assertFalse(class_exists(AnotherTestClassForAutoloader::class, false));

        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loaderMethod->expects(self::once())
            ->method('__invoke')
            ->with($reflection);

        $this->loader = new ClassLoader($loaderMethod);
        $this->loader->addClass($reflection);

        $this->expectException(FailedToLoadClass::class);
        new AnotherTestClassForAutoloader();
    }
}
