<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\Node\Stmt\Enum_ as EnumNode;
use PhpParser\Node\Stmt\Interface_ as InterfaceNode;
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
    private ReflectionNamedType|null $backingType;

    /** @var array<string, ReflectionEnumCase> */
    private array $cases;

    /** @phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found */
    private function __construct(
        private Reflector $reflector,
        EnumNode $node,
        LocatedSource $locatedSource,
        string|null $namespace = null,
    ) {
        parent::__construct($reflector, $node, $locatedSource, $namespace);

        $this->backingType = $this->createBackingType($node);
        $this->cases       = $this->createCases($node);
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
        string|null $namespace = null,
    ): self {
        return new self($reflector, $node, $locatedSource, $namespace);
    }

    public function hasCase(string $name): bool
    {
        return array_key_exists($name, $this->cases);
    }

    public function getCase(string $name): ReflectionEnumCase|null
    {
        return $this->cases[$name] ?? null;
    }

    /** @return array<string, ReflectionEnumCase> */
    public function getCases(): array
    {
        return $this->cases;
    }

    /** @return array<string, ReflectionEnumCase> */
    private function createCases(EnumNode $node): array
    {
        $enumCasesNodes = array_filter($node->stmts, static fn (Node\Stmt $stmt): bool => $stmt instanceof Node\Stmt\EnumCase);

        return array_combine(
            array_map(static fn (Node\Stmt\EnumCase $enumCaseNode): string => $enumCaseNode->name->toString(), $enumCasesNodes),
            array_map(fn (Node\Stmt\EnumCase $enumCaseNode): ReflectionEnumCase => ReflectionEnumCase::createFromNode($this->reflector, $enumCaseNode, $this), $enumCasesNodes),
        );
    }

    public function isBacked(): bool
    {
        return $this->backingType !== null;
    }

    public function getBackingType(): ReflectionNamedType
    {
        if ($this->backingType === null) {
            throw new LogicException('This enum does not have a backing type available');
        }

        return $this->backingType;
    }

    private function createBackingType(EnumNode $node): ReflectionNamedType|null
    {
        if ($node->scalarType === null) {
            return null;
        }

        $backingType = ReflectionNamedType::createFromNode($this->reflector, $this, $node->scalarType);
        assert($backingType instanceof ReflectionNamedType);

        return $backingType;
    }
}
