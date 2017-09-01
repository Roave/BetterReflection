<?php

namespace Roave\BetterReflectionBenchmark;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;

class PhpUnitTestCaseBench
{
    /**
     * @var ClassReflector
     */
    private $reflector;

    public function __construct()
    {
        $reflection = new BetterReflection();
        $this->reflector = $reflection->classReflector();
    }

    /**
     * @Subject()
     */
    public function reflect_phpunit_test_case()
    {
        $reflection = $this->reflector->reflect(TestCase::class);

        /** @var $method ReflectionMethod */
        foreach ($reflection->getMethods() as $method) {
            $method->hasReturnType() ? $method->getReturnType()->__toString() : null;

            /** @var $parameter ReflectionParameter */
            foreach ($method->getParameters() as $parameter) {
                $parameter->hasType() ? $parameter->getType()->__toString() : null;
            }
        }
    }
}

