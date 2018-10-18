<?php

declare(strict_types=1);

namespace Roave\BetterReflectionBenchmark;

use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use function array_map;
use function array_merge;

/**
 * @Iterations(5)
 */
class PhpUnitTestCaseBench
{
    /** @var ClassReflector */
    private $reflector;

    /** @var ReflectionProperty[] */
    private $properties;

    /** @var ReflectionMethod[] */
    private $methods;

    /** @var ReflectionParameter[] */
    private $parameters = [];

    public function __construct()
    {
        $reflection       = new BetterReflection();
        $this->reflector  = $reflection->classReflector();
        $reflectionClass  = $this->reflector->reflect(TestCase::class);
        $this->methods    = $reflectionClass->getMethods();
        $this->properties = $reflectionClass->getProperties();
        $this->parameters = array_merge([], ...array_map(static function (ReflectionMethod $method) : array {
            return $method->getParameters();
        }, $this->methods));
    }

    public function reflect_class() : void
    {
        $this->reflector->reflect(TestCase::class);
    }

    public function reflect_properties_doc_types() : void
    {
        foreach ($this->properties as $property) {
            $property->getDocBlockTypes();
        }
    }

    public function reflect_method_parameters() : void
    {
        foreach ($this->parameters as $parameter) {
            $parameter->getType();
        }
    }

    public function reflect_methods_parameter_doc_types() : void
    {
        foreach ($this->parameters as $parameter) {
            $parameter->getDocBlockTypes();
        }
    }

    public function reflect_methods_doc_return_types() : void
    {
        foreach ($this->methods as $method) {
            $method->getDocBlockReturnTypes();
        }
    }
}
