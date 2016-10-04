<?php

namespace Roave\BetterReflectionTest\Util;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload;
use Roave\BetterReflectionTest\Fixture\TestClassForAutoloader;

/**
 * @covers \BetterReflection\Util\Autoload
 */
class AutoloadTest extends \PHPUnit_Framework_TestCase
{
    public function testAutoloaderLoadsReflectionClassAndDeregisters()
    {
        $reflection = ReflectionClass::createFromName(TestClassForAutoloader::class);

        self::assertFalse(class_exists(TestClassForAutoloader::class, false));

        $initialAutoloaderCount = count(spl_autoload_functions());

        $testLoader = function ($classToLoad) {
            if ($classToLoad === TestClassForAutoloader::class) {
                $this->fail('Should not reach the test loader for ' . $classToLoad);
            }
            return false;
        };

        spl_autoload_register($testLoader, true, true);
        $autoloadFunctionCountA = count(spl_autoload_functions());

        Autoload::initialise();
        $autoloadFunctionCountB = count(spl_autoload_functions());

        Autoload::addClass($reflection);

        $reflection->getMethod('getValue')->setBodyFromClosure(function () {
            return 'this is the expected value - we changed it';
        });

        $c = new TestClassForAutoloader();
        $resultingValue = $c->getValue();

        Autoload::reset();
        $autoloadFunctionCountC = count(spl_autoload_functions());

        spl_autoload_unregister($testLoader);
        $autoloadFunctionCountD = count(spl_autoload_functions());

        // Note - assertions should go down here, as otherwise our autoloading can mess with phpunit's autoloading :)
        self::assertSame('this is the expected value - we changed it', $resultingValue);
        self::assertSame($initialAutoloaderCount + 1, $autoloadFunctionCountA);
        self::assertSame($initialAutoloaderCount + 2, $autoloadFunctionCountB);
        self::assertSame($initialAutoloaderCount + 1, $autoloadFunctionCountC);
        self::assertSame($initialAutoloaderCount, $autoloadFunctionCountD);
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
