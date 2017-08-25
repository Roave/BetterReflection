<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Closure;
use PhpParser\Node\FunctionLike as FunctionNode;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator;

class ReflectionFunction extends ReflectionFunctionAbstract implements Reflection
{
    /**
     * @param string $functionName
     * @return ReflectionFunction
     */
    public static function createFromName(string $functionName) : self
    {
        return (new FunctionReflector(new AutoloadSourceLocator()))->reflect($functionName);
    }

    /**
     * @param \Closure $closure
     * @return ReflectionFunction
     */
    public static function createFromClosure(Closure $closure) : self
    {
        return (new FunctionReflector(new ClosureSourceLocator($closure)))->reflect(self::CLOSURE_NAME);
    }

    /**
     * Check to see if a flag is set on this method.
     * Return string representation of this parameter
     *
     * @return string
     */
    public function __toString() : string
    {
        $paramFormat = ($this->getNumberOfParameters() > 0) ? "\n\n  - Parameters [%d] {%s\n  }" : '';

        return \sprintf(
            "Function [ <user> function %s ] {\n  @@ %s %d - %d{$paramFormat}\n}",
            $this->getName(),
            $this->getFileName(),
            $this->getStartLine(),
            $this->getEndLine(),
            \count($this->getParameters()),
            \array_reduce($this->getParameters(), function ($str, ReflectionParameter $param) : string {
                return $str . "\n    " . $param;
            }, '')
        );
    }

    /**
     * @param Reflector $reflector
     * @param FunctionNode $node
     * @param LocatedSource $locatedSource
     * @param NamespaceNode|null $namespaceNode
     * @return ReflectionFunction
     */
    public static function createFromNode(
        Reflector $reflector,
        FunctionNode $node,
        LocatedSource $locatedSource,
        ?NamespaceNode $namespaceNode = null
    ) : self {
        $function = new self();

        $function->populateFunctionAbstract($reflector, $node, $locatedSource, $namespaceNode);

        return $function;
    }

    /**
     * Check to see if this function has been disabled (by the PHP INI file
     * directive `disable_functions`).
     *
     * Note - we cannot reflect on internal functions (as there is no PHP source
     * code we can access. This means, at present, we can only EVER return false
     * from this function, because you cannot disable user-defined functions.
     *
     * @todo https://github.com/Roave/BetterReflection/issues/14
     * @see http://php.net/manual/en/ini.core.php#ini.disable-functions
     * @return bool
     */
    public function isDisabled() : bool
    {
        return false;
    }
}
