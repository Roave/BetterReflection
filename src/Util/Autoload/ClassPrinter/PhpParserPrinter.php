<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Util\Autoload\ClassPrinter;

use Roave\BetterReflection\Reflection\ReflectionClass;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\PrettyPrinter\Standard as CodePrinter;

class PhpParserPrinter implements ClassPrinterInterface
{
    public function __invoke(ReflectionClass $classInfo) : string
    {
        $nodes = [];

        if ($classInfo->inNamespace()) {
            $nodes[] = new Namespace_(new Name($classInfo->getNamespaceName()));
        }

        // @todo need to work out if we need to add `use` imports too...

        $nodes[] = $classInfo->getAst();

        return (new CodePrinter())->prettyPrint($nodes);
    }
}
