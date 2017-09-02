<?php

namespace Roave\BetterReflectionBenchmark;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionProperty;

/**
 * @Iterations(5)
 */
class PhpUnitTestCaseBench
{
    /**
     * @var ClassReflector
     */
    private $reflector;

    /**
     * @var ReflectionClass
     */
    private $reflectionClass;

    public function __construct()
    {
        $reflection = new BetterReflection();
        $this->reflector = $reflection->classReflector();
        $this->reflectionClass = $this->reflector->reflect(TestCase::class);
    }

    /**
     * @Subject()
     */
    public function reflect_class()
    {
        $this->reflector->reflect(TestCase::class);
    }

    /**
     * @Subject()
     */
    public function reflect_methods()
    {
        /** @var $method ReflectionMethod */
        foreach ($this->reflectionClass->getMethods() as $method) {
            $method->getReturnType();
        }
    }

    /**
     * @Subject()
     */
    public function reflect_method_parameters()
    {
        /** @var $method ReflectionMethod */
        foreach ($this->reflectionClass->getMethods() as $method) {
            $method->getReturnType();

            foreach ($method->getParameters() as $parameter) {
                $parameter->getType();
            }
        }
    }

    /**
     * @Subject()
     */
    public function reflect_methods_doc_return_types()
    {
        /** @var $method ReflectionMethod */
        foreach ($this->reflectionClass->getMethods() as $method) {
            $method->getDocBlockReturnTypes();
        }
    }
}

