<?php

declare(strict_types=1);

namespace Roave\BetterReflectionBenchmark;

use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\Reflector;

use function array_map;
use function array_merge;
use function array_values;

/** @Iterations(5) */
class PhpUnitTestCaseBench
{
    private Reflector $reflector;

    /** @var list<ReflectionMethod> */
    private array $methods;

    /** @var list<ReflectionParameter> */
    private array $parameters;

    public function __construct()
    {
        $reflection       = new BetterReflection();
        $this->reflector  = $reflection->reflector();
        $reflectionClass  = $this->reflector->reflectClass(TestCase::class);
        $this->methods    = array_values($reflectionClass->getMethods());
        $this->parameters = array_merge([], ...array_map(static fn (ReflectionMethod $method): array => $method->getParameters(), $this->methods));
    }

    public function benchReflectClass(): void
    {
        $this->reflector->reflectClass(TestCase::class);
    }

    public function benchReflectMethodParameters(): void
    {
        foreach ($this->parameters as $parameter) {
            $parameter->getType();
        }
    }
}
