<?php

declare(strict_types=1);

namespace Roave\BetterReflectionBenchmark;

use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\Reflector;
use function array_map;
use function array_merge;

/**
 * @Iterations(5)
 */
class PhpUnitTestCaseBench
{
    /** @var Reflector */
    private $reflector;

    /** @var list<ReflectionMethod> */
    private $methods;

    /** @var list<ReflectionParameter> */
    private $parameters = [];

    public function __construct()
    {
        $reflection       = new BetterReflection();
        $this->reflector  = $reflection->reflector();
        $reflectionClass  = $this->reflector->reflectClass(TestCase::class);
        $this->methods    = $reflectionClass->getMethods();
        $this->parameters = array_merge([], ...array_map(static function (ReflectionMethod $method) : array {
            return $method->getParameters();
        }, $this->methods));
    }

    public function benchReflectClass() : void
    {
        $this->reflector->reflectClass(TestCase::class);
    }

    public function benchReflectMethodParameters() : void
    {
        foreach ($this->parameters as $parameter) {
            $parameter->getType();
        }
    }
}
