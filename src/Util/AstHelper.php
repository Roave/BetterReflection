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
    private BetterReflection $betterReflection;

    public function __construct( ?BetterReflection $betterReflection = null ) {
        if ( !$betterReflection  ) {
            $this->betterReflection = new BetterReflection();
        } else {
            $this->betterReflection = $betterReflection;
        }
    }
    protected function getBetterReflection(): BetterReflection|null {
        return $this->betterReflection;
    }
    /**
     * 
     * @return Node[]|null $nodes
     * 
     */
    public function getAstForReflection(ReflectionClass|ReflectionFunction $reflection): array|null {
        $parser = $this->getBetterReflection()->phpParser();
        $source = $reflection->getLocatedSource();
        return $parser->parse($source->getSource());
    }
    /**
     * @return Node[]|null $nodes
     */
    public function getAstForClass(ReflectionClass $function): array|null {
        return $this->getAstForReflection($function);
    }
    /**
     * @return Node[]|null $nodes
     */
    public function getAstForFunction(ReflectionFunction $function): array|null {
        return $this->getAstForReflection($function);
    }
     /**
     * @return Node[]|null $nodes
     */
    public function getAstForClosure(ReflectionFunction $reflection): array|null {
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
    /**
     * @param Node[] $node
     */
    public function getBodyCode(array $node, PrettyPrinterAbstract|null $printer = null): String|null {
        if ( $printer == null ) {
            $printer = new StandardPrettyPrinter();
        }

       
        if ( $node[0] instanceof ArrowFunctionNode ) {       
            /** @var non-empty-list<Node\Stmt\Return_> $node **/
            $expr = $node[0]->expr;
            assert($expr instanceof Node\Expr);
            return $printer->prettyPrintExpr($expr);
        }
        if ( property_exists($node[0], 'stmts') ){ 
            
            $stmts = $node[0]->stmts;
            /** @var array<array-key, Node> $stmts */
            return $printer->prettyPrint($stmts);
        }
        return null;
    }
}