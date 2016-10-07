<?php

namespace BetterReflectionTest\Util\Autoload;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Util\Autoload\ClassLoader;
use BetterReflectionTest\Fixture\TestClassForAutoloader;

/**
 * @covers \BetterReflection\Util\Autoload\ClassLoader
 */
class ClassLoaderTest extends \PHPUnit_Framework_TestCase
{
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
