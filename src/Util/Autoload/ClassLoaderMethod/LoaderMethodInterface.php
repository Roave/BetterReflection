<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util\Autoload\ClassLoaderMethod;

use Roave\BetterReflection\Reflection\ReflectionClass;

interface LoaderMethodInterface
{
    public function __invoke(ReflectionClass $classInfo) : void;
}
