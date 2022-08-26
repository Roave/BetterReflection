<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util\Autoload\ClassPrinter;

use Roave\BetterReflection\Reflection\ReflectionClass;

/** @deprecated */
interface ClassPrinterInterface
{
    public function __invoke(ReflectionClass $classInfo): string;
}
