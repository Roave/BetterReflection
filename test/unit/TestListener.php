<?php

namespace Roave\BetterReflectionTest;

class TestListener extends \PHPUnit_Framework_BaseTestListener
{
    /**
     * @var \PHPUnit_Framework_TestSuite|null
     */
    private $currentSuite;

    /**
     * Determine the "full" test name (including the suite name if it is set)
     *
     * @param \PHPUnit_Framework_TestCase $test
     * @return string
     */
    private function getCurrentTestName(\PHPUnit_Framework_TestCase $test)
    {
        if (null === $this->currentSuite) {
            return $test->getName(true);
        }
        return $this->currentSuite->getName() . '::' . $test->getName(true);
    }

    /**
     * Create an additional assertion to ensure the specified class is not
     * loaded when executing a test
     *
     * @param string $className
     * @param \PHPUnit_Framework_TestCase $test
     */
    private function assertClassNotLoaded($className, \PHPUnit_Framework_TestCase $test)
    {
        $test->assertFalse(
            class_exists($className, false),
            'Class ' . $className . ' was loaded during test ' . $this->getCurrentTestName($test)
        );
    }

    /**
     * Ensure the fixture classes have not actually been loaded (where applicable)
     *
     * @param \PHPUnit_Framework_Test $test
     * @param float $time
     */
    public function endTest(\PHPUnit_Framework_Test $test, $time)
    {
        // Only test PHPUnit tests (i.e. no .phpt tests or anything else unexpected)
        if (!($test instanceof \PHPUnit_Framework_TestCase)) {
            return;
        }

        $this->assertClassNotLoaded(\Roave\BetterReflectionTest\Fixture\ExampleClass::class, $test);
        $this->assertClassNotLoaded(\Roave\BetterReflectionTest\FixtureOther\AnotherClass::class, $test);
        $this->assertClassNotLoaded(\ClassWithExplicitGlobalNamespace::class, $test);
        $this->assertClassNotLoaded(\ClassWithNoNamespace::class, $test);
        $this->assertClassNotLoaded(\Roave\BetterReflectionTest\Fixture\Methods::class, $test);
    }

    /**
     * Simply record the "current" test suite being run
     * Used by getCurrentTestName().
     *
     * @param \PHPUnit_Framework_TestSuite $suite
     */
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $this->currentSuite = $suite;
    }

    /**
     * Unset the "current" test suite being run at the end.
     * Used by getCurrentTestName().
     *
     * @param \PHPUnit_Framework_TestSuite $suite
     */
    public function endTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $this->currentSuite = null;
    }
}
