<?php

namespace Roave\BetterReflection\Util\Autoload\ClassLoaderMethod;

use Roave\BetterReflection\Reflection\ReflectionClass;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\PrettyPrinter\Standard as CodePrinter;

class EvalLoader implements LoaderMethodInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(ReflectionClass $classInfo)
    {
        $nodes = [];

        if ($classInfo->inNamespace()) {
            $nodes[] = new Namespace_(new Name($classInfo->getNamespaceName()));
        }

        // @todo need to work out if we need to add `use` imports too...

        $nodes[] = $classInfo->getAst();

        eval((new CodePrinter())->prettyPrint($nodes));
    }
}
