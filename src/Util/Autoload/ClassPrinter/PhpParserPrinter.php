<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Util\Autoload\ClassPrinter;

use phpDocumentor\Reflection\Types\ContextFactory;
use PhpParser\Builder\Use_ as BuilderUse_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\PrettyPrinter\Standard as CodePrinter;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class PhpParserPrinter implements ClassPrinterInterface
{
    public function __invoke(ReflectionClass $classInfo) : string
    {
        $nodes = [];

        if ($classInfo->inNamespace()) {
            $nodes[] = new Namespace_(new Name($classInfo->getNamespaceName()));
        }

        $imports = (new ContextFactory())->createForNamespace(
            $classInfo->getNamespaceName(),
            $classInfo->getLocatedSource()->getSource()
        )->getNamespaceAliases();

        foreach ($imports as $alias => $fullyQualified) {
            $nodes[] = (new BuilderUse_($fullyQualified, Use_::TYPE_NORMAL))->as($alias)->getNode();
        }

        $nodes[] = $classInfo->getAst();

        return (new CodePrinter())->prettyPrint($nodes);
    }
}
