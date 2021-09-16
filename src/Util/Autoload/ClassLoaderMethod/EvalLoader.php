<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util\Autoload\ClassLoaderMethod;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\ClassPrinter\ClassPrinterInterface;

final class EvalLoader implements LoaderMethodInterface
{
    public function __construct(private ClassPrinterInterface $classPrinter)
    {
    }

    public function __invoke(ReflectionClass $classInfo): void
    {
        eval($this->classPrinter->__invoke($classInfo));
    }
}
