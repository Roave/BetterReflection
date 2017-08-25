<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest;

use PHPUnit\Framework\BaseTestListener;
use PHPUnit\Framework\TestCase;

class TestListener extends BaseTestListener
{
    /**
     * @var \PHPUnit\Framework\TestSuite|null
     */
    private $currentSuite;

    /**
     * Determine the "full" test name (including the suite name if it is set)
     *
     * @param TestCase $test
     * @return string
     */
    private function getCurrentTestName(TestCase $test) : string
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
     * @param TestCase $test
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    private function assertClassNotLoaded(string $className, TestCase $test) : void
    {
        TestCase::assertFalse(
            \class_exists($className, false),
            'Class ' . $className . ' was loaded during test ' . $this->getCurrentTestName($test)
        );
    }

    /**
     * Ensure the fixture classes have not actually been loaded (where applicable)
     *
     * @param \PHPUnit\Framework\Test $test
     * @param float $time
     * @throws \PHPUnit\Framework\AssertionFailedError
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function endTest(\PHPUnit\Framework\Test $test, $time) : void
    {
        // Only test PHPUnit tests (i.e. no .phpt tests or anything else unexpected)
        if ( ! ($test instanceof TestCase)) {
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
     * @param \PHPUnit\Framework\TestSuite $suite
     */
    public function startTestSuite(\PHPUnit\Framework\TestSuite $suite) : void
    {
        $this->currentSuite = $suite;
    }

    /**
     * Unset the "current" test suite being run at the end.
     * Used by getCurrentTestName().
     *
     * @param \PHPUnit\Framework\TestSuite $suite
     */
    public function endTestSuite(\PHPUnit\Framework\TestSuite $suite) : void
    {
        $this->currentSuite = null;
    }
}
