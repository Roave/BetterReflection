<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\Node\Stmt\Enum_ as EnumNode;
use PhpParser\Node\Stmt\Interface_ as InterfaceNode;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use PhpParser\Node\Stmt\Trait_ as TraitNode;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

use function array_combine;
use function array_filter;
use function array_key_exists;
use function array_map;
use function assert;

class ReflectionEnum extends ReflectionClass
{
    /** @var array<string, ReflectionEnumCase>|null */
    private array|null $cachedCases = null;

    /** @phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found */
    protected function __construct(
        private Reflector $reflector,
        private EnumNode $node,
        LocatedSource $locatedSource,
        NamespaceNode|null $declaringNamespace = null,
    ) {
        parent::__construct($reflector, $node, $locatedSource, $declaringNamespace);
    }

    /**
     * @internal
     *
     * @param EnumNode $node
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public static function createFromNode(
        Reflector $reflector,
        ClassNode|InterfaceNode|TraitNode|EnumNode $node,
        LocatedSource $locatedSource,
        NamespaceNode|null $namespace = null,
    ): self {
        return new self($reflector, $node, $locatedSource, $namespace);
    }

    public function hasCase(string $name): bool
    {
        $cases = $this->getCases();

        return array_key_exists($name, $cases);
    }

    public function getCase(string $name): ReflectionEnumCase|null
    {
        $cases = $this->getCases();

        return $cases[$name] ?? null;
    }

    /** @return array<string, ReflectionEnumCase> */
    public function getCases(): array
    {
        if ($this->cachedCases === null) {
            $casesNodes = array_filter($this->node->stmts, static fn (Node\Stmt $stmt): bool => $stmt instanceof Node\Stmt\EnumCase);

            $this->cachedCases = array_combine(
                array_map(static fn (Node\Stmt\EnumCase $node): string => $node->name->toString(), $casesNodes),
                array_map(fn (Node\Stmt\EnumCase $node): ReflectionEnumCase => ReflectionEnumCase::createFromNode($this->reflector, $node, $this), $casesNodes),
            );
        }

        return $this->cachedCases;
    }

    public function isBacked(): bool
    {
        return $this->node->scalarType !== null;
    }

    public function getBackingType(): ReflectionNamedType
    {
        if ($this->node->scalarType === null) {
            throw new LogicException('This enum does not have a backing type available');
        }

        $backingType = ReflectionNamedType::createFromNode($this->reflector, $this, $this->node->scalarType);
        assert($backingType instanceof ReflectionNamedType);

        return $backingType;
    }
}
