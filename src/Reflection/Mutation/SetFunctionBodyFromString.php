<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Mutation;

use PhpParser\Parser;
use Roave\BetterReflection\Reflection\Mutator\ReflectionFunctionAbstractMutator;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;

class SetFunctionBodyFromString
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
     * @param string $string
     * @return ReflectionMethod|ReflectionFunction
     */
    public function __invoke(ReflectionFunctionAbstract $functionAbstractReflection, string $string) : ReflectionFunctionAbstract
    {
        $node        = clone $functionAbstractReflection->getAst();
        $node->stmts = $this->parser->parse('<?php ' . $string);

        return $this->mutator->__invoke($functionAbstractReflection, $node);
    }
}
