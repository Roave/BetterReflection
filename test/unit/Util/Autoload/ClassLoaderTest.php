<?php

namespace BetterReflectionTest\Util\Autoload;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Util\Autoload\ClassLoader;
use BetterReflection\Util\Autoload\ClassLoaderMethod\EvalLoader;
use BetterReflection\Util\Autoload\ClassLoaderMethod\LoaderMethodInterface;
use BetterReflectionTest\Fixture\TestClassForAutoloader;

/**
 * @covers \BetterReflection\Util\Autoload\ClassLoader
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

        $loader = new ClassLoader(new EvalLoader());
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
