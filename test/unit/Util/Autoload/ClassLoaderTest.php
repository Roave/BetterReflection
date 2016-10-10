<?php

namespace Roave\BetterReflectionTest\Util\Autoload;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\ClassLoader;
use Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\EvalLoader;
use Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\LoaderMethodInterface;
use Roave\BetterReflection\Util\Autoload\ClassPrinter\PhpParserPrinter;
use Roave\BetterReflectionTest\Fixture\TestClassForAutoloader;

/**
 * @covers \Roave\BetterReflection\Util\Autoload\ClassLoader
 */
class ClassLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testAutoloadSelfRegisters()
    {
        $initialAutoloaderCount = count(spl_autoload_functions());

        $loader = new ClassLoader($this->createMock(LoaderMethodInterface::class));

        self::assertCount($initialAutoloaderCount + 1, spl_autoload_functions());

        spl_autoload_unregister($loader);

        self::assertCount($initialAutoloaderCount, spl_autoload_functions());
    }

    public function testAutoloadTriggersLoaderMethod()
    {
        $reflection = ReflectionClass::createFromName(TestClassForAutoloader::class);
        self::assertFalse(class_exists(TestClassForAutoloader::class, false));

        $loader = new ClassLoader(new EvalLoader(new PhpParserPrinter()));
        $loader->addClass($reflection);

        new TestClassForAutoloader();
    }

    public function testAddClassThrowsExceptionWhenAutoloadNotInitialised()
    {
        $this->markTestIncomplete(__METHOD__);
    }

    public function testAddClassThrowsExceptionWhenClassAlreadyRegisteredInAutoload()
    {
        $this->markTestIncomplete(__METHOD__);
    }

    public function testAddClassThrowsExceptionWhenClassAlreadyLoaded()
    {
        $this->markTestIncomplete(__METHOD__);
    }

    public function testInitailiseCannotBeCalledTwice()
    {
        $this->markTestIncomplete(__METHOD__);
    }

    public function testAutoloadStackObeyedWhenClassNotRegisteredInAutoload()
    {
        $this->markTestIncomplete(__METHOD__);
    }

    public function testAutoloadThrowsExceptionWhenClassIsNotLoadedCorrectlyAfterAttemptingToLoad()
    {
        $this->markTestIncomplete(__METHOD__);
    }
}
