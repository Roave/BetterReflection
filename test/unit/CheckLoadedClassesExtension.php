<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest;

use ClassWithExplicitGlobalNamespace;
use ClassWithNoNamespace;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Roave\BetterReflectionTest\Fixture\ExampleClass;
use Roave\BetterReflectionTest\Fixture\Methods;
use Roave\BetterReflectionTest\FixtureOther\AnotherClass;

use function class_exists;
use function sprintf;

class CheckLoadedClassesExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new class implements FinishedSubscriber {
            public function notify(Finished $event): void
            {
                $this->assertClassNotLoaded(ExampleClass::class, $event);
                $this->assertClassNotLoaded(AnotherClass::class, $event);
                $this->assertClassNotLoaded(ClassWithExplicitGlobalNamespace::class, $event);
                $this->assertClassNotLoaded(ClassWithNoNamespace::class, $event);
                $this->assertClassNotLoaded(Methods::class, $event);
            }

            /**
             * Create an additional assertion to ensure the specified class is not
             * loaded when executing a test
             *
             * @throws AssertionFailedError
             */
            private function assertClassNotLoaded(string $className, Finished $event): void
            {
                TestCase::assertFalse(
                    class_exists($className, false),
                    sprintf('Class %s was loaded during test %s', $className, $event->test()->id()),
                );
            }
        });
    }
}
