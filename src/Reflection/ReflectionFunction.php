<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Exception\FunctionDoesNotExist;
use Roave\BetterReflection\Reflection\StringCast\ReflectionFunctionStringCast;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator;

use function assert;
use function function_exists;

class ReflectionFunction implements Reflection
{
    use ReflectionFunctionAbstract;

    public const CLOSURE_NAME = '{closure}';

    private Node\Stmt\Function_|Node\Expr\Closure|Node\Expr\ArrowFunction $functionNode;

    private function __construct(
        private Reflector $reflector,
        private Node\Stmt\ClassMethod|Node\Stmt\Function_|Node\Expr\Closure|Node\Expr\ArrowFunction $node,
        private LocatedSource $locatedSource,
        private NamespaceNode|null $declaringNamespace = null,
    ) {
        assert($node instanceof Node\Stmt\Function_ || $node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction);

        $this->functionNode = $node;
    }

    /** @throws IdentifierNotFound */
    public static function createFromName(string $functionName): self
    {
        return (new BetterReflection())->reflector()->reflectFunction($functionName);
    }

    /** @throws IdentifierNotFound */
    public static function createFromClosure(Closure $closure): self
    {
        $configuration = new BetterReflection();

        return (new DefaultReflector(new AggregateSourceLocator([
            $configuration->sourceLocator(),
            new ClosureSourceLocator($closure, $configuration->phpParser()),
        ])))->reflectFunction(self::CLOSURE_NAME);
    }

    public function __toString(): string
    {
        return ReflectionFunctionStringCast::toString($this);
    }

    /** @internal */
    public static function createFromNode(
        Reflector $reflector,
        Node\Stmt\Function_|Node\Expr\Closure|Node\Expr\ArrowFunction $node,
        LocatedSource $locatedSource,
        NamespaceNode|null $namespaceNode = null,
    ): self {
        return new self($reflector, $node, $locatedSource, $namespaceNode);
    }

    public function getAst(): Node\Stmt\Function_|Node\Expr\Closure|Node\Expr\ArrowFunction
    {
        return $this->functionNode;
    }

    /**
     * Get the "short" name of the function (e.g. for A\B\foo, this will return
     * "foo").
     */
    public function getShortName(): string
    {
        if ($this->functionNode instanceof Node\Expr\Closure || $this->functionNode instanceof Node\Expr\ArrowFunction) {
            return self::CLOSURE_NAME;
        }

        return $this->functionNode->name->name;
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

    public function isStatic(): bool
    {
        $node = $this->getAst();

        return ($node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction) && $node->static;
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

        return static fn (mixed ...$args): mixed => $functionName(...$args);
    }

    /**
     * @throws NotImplemented
     * @throws FunctionDoesNotExist
     */
    public function invoke(mixed ...$args): mixed
    {
        return $this->invokeArgs($args);
    }

    /**
     * @param array<mixed> $args
     *
     * @throws NotImplemented
     * @throws FunctionDoesNotExist
     */
    public function invokeArgs(array $args = []): mixed
    {
        $this->assertIsNoClosure();

        $functionName = $this->getName();

        $this->assertFunctionExist($functionName);

        return $functionName(...$args);
    }

    /** @throws NotImplemented */
    private function assertIsNoClosure(): void
    {
        if ($this->isClosure()) {
            throw new NotImplemented('Not implemented for closures');
        }
    }

    /** @throws FunctionDoesNotExist */
    private function assertFunctionExist(string $functionName): void
    {
        if (! function_exists($functionName)) {
            throw FunctionDoesNotExist::fromName($functionName);
        }
    }
}
