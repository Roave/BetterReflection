<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutation;

use Closure;
use PhpParser\Parser;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Mutator\ReflectionFunctionAbstractMutator;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator;

class SetFunctionBodyFromClosure
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var ReflectionFunctionAbstractMutator
     */
    private $mutator;

    public function __construct(Parser $parser)
    {
        $this->parser  = $parser;
        $this->mutator = new ReflectionFunctionAbstractMutator();
    }

    /**
     * @param ReflectionMethod|ReflectionFunction $functionReflection
     * @param Closure $closure
     * @return ReflectionMethod|ReflectionFunction
     */
    public function __invoke(ReflectionFunctionAbstract $functionAbstractReflection, Closure $closure) : ReflectionFunctionAbstract
    {
        $reflector = Closure::bind(function () : Reflector {
            return $this->reflector;
        }, $functionAbstractReflection, ReflectionFunctionAbstract::class)->__invoke();

        /** @var ReflectionFunction $closureReflection */
        $closureReflection = (new ClosureSourceLocator($closure, $this->parser))->locateIdentifier($reflector, new Identifier(ReflectionFunctionAbstract::CLOSURE_NAME, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)));

        $node        = clone $functionAbstractReflection->getAst();
        $node->stmts = $closureReflection->getAst()->stmts;

        return $this->mutator->__invoke($functionAbstractReflection, $node);
    }
}
