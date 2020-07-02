<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Closure;
use PhpParser\Node;
use PhpParser\Node\FunctionLike as FunctionNode;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Exception\FunctionDoesNotExist;
use Roave\BetterReflection\Reflection\StringCast\ReflectionFunctionStringCast;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator;

use function function_exists;

class ReflectionFunction extends ReflectionFunctionAbstract implements Reflection
{
    /**
     * @throws IdentifierNotFound
     */
    public static function createFromName(string $functionName): self
    {
        return (new BetterReflection())->functionReflector()->reflect($functionName);
    }

    /**
     * @throws IdentifierNotFound
     */
    public static function createFromClosure(Closure $closure): self
    {
        $configuration = new BetterReflection();

        return (new FunctionReflector(
            new ClosureSourceLocator($closure, $configuration->phpParser()),
            $configuration->classReflector(),
        ))->reflect(self::CLOSURE_NAME);
    }

    public function __toString(): string
    {
        return ReflectionFunctionStringCast::toString($this);
    }

    /**
     * @internal
     *
     * @param Node\Stmt\ClassMethod|Node\Stmt\Function_|Node\Expr\Closure $node Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     */
    public static function createFromNode(
        Reflector $reflector,
        FunctionNode $node,
        LocatedSource $locatedSource,
        ?NamespaceNode $namespaceNode = null
    ): self {
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
     * @see https://php.net/manual/en/ini.core.php#ini.disable-functions
     *
     * @todo https://github.com/Roave/BetterReflection/issues/14
     */
    public function isDisabled(): bool
    {
        return false;
    }

    /**
     * @throws NotImplemented
     * @throws FunctionDoesNotExist
     */
    public function getClosure(): Closure
    {
        $this->assertIsNoClosure();

        $functionName = $this->getName();

        $this->assertFunctionExist($functionName);

        return static function (...$args) use ($functionName) {
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
    private function assertIsNoClosure(): void
    {
        if ($this->isClosure()) {
            throw new NotImplemented('Not implemented for closures');
        }
    }

    /**
     * @throws FunctionDoesNotExist
     *
     * @psalm-assert callable-string $functionName
     */
    private function assertFunctionExist(string $functionName): void
    {
        if (! function_exists($functionName)) {
            throw FunctionDoesNotExist::fromName($functionName);
        }
    }
}
