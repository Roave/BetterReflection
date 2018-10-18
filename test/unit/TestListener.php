<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest;

use ClassWithExplicitGlobalNamespace;
use ClassWithNoNamespace;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestListener as BaseTestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;
use Roave\BetterReflectionTest\Fixture\ExampleClass;
use Roave\BetterReflectionTest\Fixture\Methods;
use Roave\BetterReflectionTest\FixtureOther\AnotherClass;
use function class_exists;

class TestListener implements BaseTestListener
{
    use TestListenerDefaultImplementation;

    /** @var TestSuite|null */
    private $currentSuite;

    /**
     * Determine the "full" test name (including the suite name if it is set)
     */
    private function getCurrentTestName(TestCase $test) : string
    {
        if ($this->currentSuite === null) {
            return $test->getName(true);
        }
        return $this->currentSuite->getName() . '::' . $test->getName(true);
    }

    /**
     * Create an additional assertion to ensure the specified class is not
     * loaded when executing a test
     *
     * @throws AssertionFailedError
     */
    private function assertClassNotLoaded(string $className, TestCase $test) : void
    {
        TestCase::assertFalse(
            class_exists($className, false),
            'Class ' . $className . ' was loaded during test ' . $this->getCurrentTestName($test)
        );
    }

    /**
     * Ensure the fixture classes have not actually been loaded (where applicable)
     *
     * @throws AssertionFailedError
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function endTest(Test $test, float $time) : void
    {
        // Only test PHPUnit tests (i.e. no .phpt tests or anything else unexpected)
        if (! ($test instanceof TestCase)) {
            return;
        }

        $this->assertClassNotLoaded(ExampleClass::class, $test);
        $this->assertClassNotLoaded(AnotherClass::class, $test);
        $this->assertClassNotLoaded(ClassWithExplicitGlobalNamespace::class, $test);
        $this->assertClassNotLoaded(ClassWithNoNamespace::class, $test);
        $this->assertClassNotLoaded(Methods::class, $test);
    }

    /**
     * Simply record the "current" test suite being run
     * Used by getCurrentTestName().
     */
    public function startTestSuite(TestSuite $suite) : void
    {
        $this->currentSuite = $suite;
    }

    /**
     * Unset the "current" test suite being run at the end.
     * Used by getCurrentTestName().
     */
    public function endTestSuite(TestSuite $suite) : void
    {
        $this->currentSuite = null;
    }
}
