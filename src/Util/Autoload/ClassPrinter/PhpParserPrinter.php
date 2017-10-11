<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Util\Autoload\ClassPrinter;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\PrettyPrinter\Standard as CodePrinter;
use Rector\BetterReflection\Reflection\ReflectionClass;

final class PhpParserPrinter implements ClassPrinterInterface
{
    public function __invoke(ReflectionClass $classInfo) : string
    {
        $nodes = [];

        if ($classInfo->inNamespace()) {
            $nodes[] = new Namespace_(new Name($classInfo->getNamespaceName()));
        }

        $nodes[] = $classInfo->getAst();

        return (new CodePrinter())->prettyPrint($nodes);
    }
}
