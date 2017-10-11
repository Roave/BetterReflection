<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Util\Autoload\ClassLoaderMethod;

use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Util\Autoload\ClassPrinter\ClassPrinterInterface;

final class EvalLoader implements LoaderMethodInterface
{
    /**
     * @var ClassPrinterInterface
     */
    private $classPrinter;

    public function __construct(ClassPrinterInterface $classPrinter)
    {
        $this->classPrinter = $classPrinter;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(ReflectionClass $classInfo) : void
    {
        eval($this->classPrinter->__invoke($classInfo));
    }
}
