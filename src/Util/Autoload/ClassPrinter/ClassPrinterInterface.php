<?php

namespace Roave\BetterReflection\Util\Autoload\ClassPrinter;

use Roave\BetterReflection\Reflection\ReflectionClass;

interface ClassPrinterInterface
{
    /**
     * @param ReflectionClass $classInfo
     * @return string
     */
    public function __invoke(ReflectionClass $classInfo);
}
