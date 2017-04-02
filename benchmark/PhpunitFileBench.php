<?php

namespace Roave\BetterReflectionBenchmark;

use Roave\BetterReflectionBenchmark\source\PhpunitTestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;

/**
 * @OutputTimeUnit("seconds")
 */
class PhpunitFileBench
{
    /**
     * @Subject()
     */
    public function reflect_phpunit_class()
    {
        ReflectionClass::createFromName(PhpunitTestCase::class);
    }

    /**
     * @Subject()
     */
    public function reflection_to_string()
    {
        $reflection = ReflectionClass::createFromName(PhpunitTestCase::class);
        $reflection->__toString();
    }

}
