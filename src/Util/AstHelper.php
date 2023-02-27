<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use Closure;
use PhpParser\Node;

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;

use PhpParser\Node\Expr\Closure as ClosureNode;
use PhpParser\Node\Expr\ClosureUse as ClosureUseNode;
use PhpParser\Node\Expr\ArrowFunction as ArrowFunctionNode;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use PhpParser\PrettyPrinter\Standard as StandardPrettyPrinter;
use PhpParser\PrettyPrinterAbstract;



final class AstHelper {
    public function __construct(protected ?BetterReflection $betterReflection = null ) {
        if ( !$betterReflection  ) {
            $this->betterReflection = new BetterReflection();
        }
    }
    protected function getBetterReflection(): BetterReflection {
        return $this->betterReflection;
    }
    public function forReflection(Reflection $reflection): Node\Stmt|Array {
        $parser = $this->getBetterReflection()->phpParser();
        $source = $reflection->getLocatedSource();
        return $parser->parse($source->getSource());
    }
    public function forClass(ReflectionClass $function): Node\Stmt|array {
        return $this->forReflection($function);
    }
    public function forFunction(ReflectionFunction $function): Node\Stmt|array {
        return $this->forReflection($function);
    }
    // should return the body.
    public function forClosure(ReflectionFunction $reflection): Node\Stmt|array {
        $parser = $this->getBetterReflection()->phpParser();
        
        // can this be done better/more efficient using the parser itself?
        $file = $reflection->getFileName();
        $start = $reflection->getStartLine();
        $end = $reflection->getEndLine();

        
        $source = explode("\n",$reflection->getLocatedSource()->getSource());
        $source = array_slice($source, $start - 1, $end - $start + 1);
        $source = implode('', $source);
        
        $ast = $parser->parse('<?php '.trim($source));
        
        $visitor   = new FindingVisitor(
            static function (Node $node): bool {
                return $node instanceof ClosureNode || $node instanceof ClosureUseNode || $node instanceof ArrowFunctionNode ;
            }
        );
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);
        $nodes = $visitor->getFoundNodes();

        return $nodes;
    }

    public function getBodyCode(Node\Stmt|array $node, PrettyPrinterAbstract|null $printer = null) {
        if ( $printer == null ) {
            $printer = new StandardPrettyPrinter();
        }

       
        if ( $node[0] instanceof ArrowFunctionNode ) {       
            /** @var non-empty-list<Node\Stmt\Return_> $ast **/
            $expr = $node[0]->expr;
            assert($expr instanceof Node\Expr);
            return $printer->prettyPrintExpr($expr);
        }
        return $printer->prettyPrint($node[0]->stmts);
    }
}