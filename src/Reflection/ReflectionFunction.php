<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Reflection;

use Closure;
use PhpParser\Node\FunctionLike as FunctionNode;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use Rector\BetterReflection\BetterReflection;
use Rector\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Rector\BetterReflection\Reflection\Exception\FunctionDoesNotExist;
use Rector\BetterReflection\Reflection\StringCast\ReflectionFunctionStringCast;
use Rector\BetterReflection\Reflector\FunctionReflector;
use Rector\BetterReflection\Reflector\Reflector;
use Rector\BetterReflection\SourceLocator\Located\LocatedSource;
use Rector\BetterReflection\SourceLocator\Type\ClosureSourceLocator;

class ReflectionFunction extends ReflectionFunctionAbstract implements Reflection
{
    /**
     * @param string $functionName
     *
     * @return ReflectionFunction
     *
     * @throws \Rector\BetterReflection\Reflector\Exception\IdentifierNotFound
     */
    public static function createFromName(string $functionName) : self
    {
        return (new BetterReflection())->functionReflector()->reflect($functionName);
    }

    /**
     * @param \Closure $closure
     *
     * @return ReflectionFunction
     *
     * @throws \Rector\BetterReflection\Reflector\Exception\IdentifierNotFound
     */
    public static function createFromClosure(Closure $closure) : self
    {
        $configuration = new BetterReflection();

        return (new FunctionReflector(
            new ClosureSourceLocator($closure, $configuration->phpParser()),
            $configuration->classReflector()
        ))->reflect(self::CLOSURE_NAME);
    }

    public function __toString() : string
    {
        return ReflectionFunctionStringCast::toString($this);
    }

    /**
     * @internal
     * @param Reflector $reflector
     * @param FunctionNode $node Node has to be processed by the PhpParser\NodeVisitor\NameResolver
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

    /**
     * Note - we cannot reflect on internal functions (as there is no PHP source
     * code we can access. This means, at present, we can only EVER return null
     * from this function.

     * @return string|null
     */
    public function getExtensionName() : ?string
    {
        return null;
    }

    /**
     * @return Closure
     *
     * @throws NotImplemented
     * @throws FunctionDoesNotExist
     */
    public function getClosure() : Closure
    {
        $this->assertIsNoClosure();

        $functionName = $this->getName();

        $this->assertFunctionExist($functionName);

        return function (...$args) use ($functionName) {
            return $functionName(...$args);
        };
    }

    /**
     * @param mixed ...$args
     *
     * @return mixed
     *
     * @throws NotImplemented
     * @throws FunctionDoesNotExist
     */
    public function invoke(...$args)
    {
        return $this->invokeArgs($args);
    }

    /**
     * @param mixed[] $args
     *
     * @return mixed
     *
     * @throws NotImplemented
     * @throws FunctionDoesNotExist
     */
    public function invokeArgs(array $args = [])
    {
        $this->assertIsNoClosure();

        $functionName = $this->getName();

        $this->assertFunctionExist($functionName);

        return $functionName(...$args);
    }

    /**
     * @throws NotImplemented
     */
    private function assertIsNoClosure() : void
    {
        if ($this->isClosure()) {
            throw new NotImplemented('Not implemented for closures');
        }
    }

    /**
     * @param string $functionName
     *
     * @throws FunctionDoesNotExist
     */
    private function assertFunctionExist(string $functionName) : void
    {
        if ( ! \function_exists($functionName)) {
            throw FunctionDoesNotExist::fromName($functionName);
        }
    }
}
