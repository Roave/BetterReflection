<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use OutOfBoundsException;
use ReflectionClass as CoreReflectionClass;
use ReflectionEnum as CoreReflectionEnum;
use ReflectionException as CoreReflectionException;
use ReflectionExtension as CoreReflectionExtension;
use ReflectionMethod as CoreReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionEnum as BetterReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionEnumCase as BetterReflectionEnumCase;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use Roave\BetterReflection\Util\FileHelper;
use ValueError;

use function array_combine;
use function array_map;
use function array_values;
use function constant;
use function sprintf;
use function strtolower;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-immutable
 */
final class ReflectionEnum extends CoreReflectionEnum
{
    public function __construct(private BetterReflectionEnum $betterReflectionEnum)
    {
        unset($this->name);
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->betterReflectionEnum->__toString();
    }

    public function __get(string $name): mixed
    {
        if ($name === 'name') {
            return $this->betterReflectionEnum->getName();
        }

        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }

    public function getName(): string
    {
        return $this->betterReflectionEnum->getName();
    }

    public function isAnonymous(): bool
    {
        return $this->betterReflectionEnum->isAnonymous();
    }

    public function isInternal(): bool
    {
        return $this->betterReflectionEnum->isInternal();
    }

    public function isUserDefined(): bool
    {
        return $this->betterReflectionEnum->isUserDefined();
    }

    public function isInstantiable(): bool
    {
        return $this->betterReflectionEnum->isInstantiable();
    }

    public function isCloneable(): bool
    {
        return $this->betterReflectionEnum->isCloneable();
    }

    /** @return non-empty-string|false */
    public function getFileName(): string|false
    {
        $fileName = $this->betterReflectionEnum->getFileName();

        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : false;
    }

    public function getStartLine(): int|false
    {
        return $this->betterReflectionEnum->getStartLine();
    }

    public function getEndLine(): int|false
    {
        return $this->betterReflectionEnum->getEndLine();
    }

    public function getDocComment(): string|false
    {
        return $this->betterReflectionEnum->getDocComment() ?? false;
    }

    public function getConstructor(): CoreReflectionMethod|null
    {
        $constructor = $this->betterReflectionEnum->getConstructor();

        if ($constructor === null) {
            return null;
        }

        return new ReflectionMethod($constructor);
    }

    public function hasMethod(string $name): bool
    {
        if ($name === '') {
            return false;
        }

        return $this->betterReflectionEnum->hasMethod($name);
    }

    public function getMethod(string $name): ReflectionMethod
    {
        $method = $name !== '' ? $this->betterReflectionEnum->getMethod($name) : null;

        if ($method === null) {
            throw new CoreReflectionException(sprintf('Method %s::%s() does not exist', $this->betterReflectionEnum->getName(), $name));
        }

        return new ReflectionMethod($method);
    }

    /**
     * @param int-mask-of<ReflectionMethod::IS_*>|null $filter
     *
     * @return list<ReflectionMethod>
     */
    public function getMethods(int|null $filter = null): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_values(array_map(
            static fn (BetterReflectionMethod $method): ReflectionMethod => new ReflectionMethod($method),
            $this->betterReflectionEnum->getMethods($filter ?? 0),
        ));
    }

    public function hasProperty(string $name): bool
    {
        if ($name === '') {
            return false;
        }

        return $this->betterReflectionEnum->hasProperty($name);
    }

    public function getProperty(string $name): ReflectionProperty
    {
        $betterReflectionProperty = $name !== '' ? $this->betterReflectionEnum->getProperty($name) : null;

        if ($betterReflectionProperty === null) {
            throw new CoreReflectionException(sprintf('Property %s::$%s does not exist', $this->betterReflectionEnum->getName(), $name));
        }

        return new ReflectionProperty($betterReflectionProperty);
    }

    /**
     * @param int-mask-of<ReflectionProperty::IS_*>|null $filter
     *
     * @return list<ReflectionProperty>
     */
    public function getProperties(int|null $filter = null): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_values(array_map(
            static fn (BetterReflectionProperty $property): ReflectionProperty => new ReflectionProperty($property),
            $this->betterReflectionEnum->getProperties($filter ?? 0),
        ));
    }

    public function hasConstant(string $name): bool
    {
        if ($name === '') {
            return false;
        }

        return $this->betterReflectionEnum->hasCase($name) || $this->betterReflectionEnum->hasConstant($name);
    }

    /**
     * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
     *
     * @return array<non-empty-string, mixed>
     */
    public function getConstants(int|null $filter = null): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_map(
            fn (BetterReflectionClassConstant|BetterReflectionEnumCase $betterConstantOrEnumCase): mixed => $this->getConstantValue($betterConstantOrEnumCase),
            $this->filterBetterReflectionClassConstants($filter),
        );
    }

    public function getConstant(string $name): mixed
    {
        if ($name === '') {
            return false;
        }

        $enumCase = $this->betterReflectionEnum->getCase($name);
        if ($enumCase !== null) {
            return $this->getConstantValue($enumCase);
        }

        $betterReflectionConstant = $this->betterReflectionEnum->getConstant($name);
        if ($betterReflectionConstant === null) {
            return false;
        }

        return $betterReflectionConstant->getValue();
    }

    private function getConstantValue(BetterReflectionClassConstant|BetterReflectionEnumCase $betterConstantOrEnumCase): mixed
    {
        if ($betterConstantOrEnumCase instanceof BetterReflectionEnumCase) {
            return constant(sprintf('%s::%s', $betterConstantOrEnumCase->getDeclaringClass()->getName(), $betterConstantOrEnumCase->getName()));
        }

        return $betterConstantOrEnumCase->getValue();
    }

    public function getReflectionConstant(string $name): ReflectionClassConstant|false
    {
        if ($name === '') {
            return false;
        }

        // @infection-ignore-all Coalesce: There's no difference
        $betterReflectionConstantOrEnumCase = $this->betterReflectionEnum->getCase($name) ?? $this->betterReflectionEnum->getConstant($name);
        if ($betterReflectionConstantOrEnumCase === null) {
            return false;
        }

        return new ReflectionClassConstant($betterReflectionConstantOrEnumCase);
    }

    /**
     * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
     *
     * @return list<ReflectionClassConstant>
     */
    public function getReflectionConstants(int|null $filter = null): array
    {
        return array_values(array_map(
            static fn (BetterReflectionClassConstant|BetterReflectionEnumCase $betterConstantOrEnum): ReflectionClassConstant => new ReflectionClassConstant($betterConstantOrEnum),
            $this->filterBetterReflectionClassConstants($filter),
        ));
    }

    /**
     * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
     *
     * @return array<non-empty-string, BetterReflectionClassConstant|BetterReflectionEnumCase>
     */
    private function filterBetterReflectionClassConstants(int|null $filter): array
    {
        $reflectionConstants = $this->betterReflectionEnum->getConstants($filter ?? 0);

        if (
            $filter === null
            || $filter & ReflectionClassConstant::IS_PUBLIC
        ) {
            $reflectionConstants += $this->betterReflectionEnum->getCases();
        }

        return $reflectionConstants;
    }

    /** @return array<class-string, CoreReflectionClass> */
    public function getInterfaces(): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_map(
            static fn (BetterReflectionClass $interface): ReflectionClass => new ReflectionClass($interface),
            $this->betterReflectionEnum->getInterfaces(),
        );
    }

    /** @return list<class-string> */
    public function getInterfaceNames(): array
    {
        return $this->betterReflectionEnum->getInterfaceNames();
    }

    public function isInterface(): bool
    {
        return $this->betterReflectionEnum->isInterface();
    }

    /** @return array<trait-string, CoreReflectionClass> */
    public function getTraits(): array
    {
        $traits = $this->betterReflectionEnum->getTraits();

        /** @var list<trait-string> $traitNames */
        $traitNames = array_map(static fn (BetterReflectionClass $trait): string => $trait->getName(), $traits);

        /** @psalm-suppress ImpureFunctionCall */
        return array_combine(
            $traitNames,
            array_map(static fn (BetterReflectionClass $trait): ReflectionClass => new ReflectionClass($trait), $traits),
        );
    }

    /** @return list<trait-string> */
    public function getTraitNames(): array
    {
        return $this->betterReflectionEnum->getTraitNames();
    }

    /** @return array<non-empty-string, non-empty-string> */
    public function getTraitAliases(): array
    {
        return $this->betterReflectionEnum->getTraitAliases();
    }

    public function isTrait(): bool
    {
        return $this->betterReflectionEnum->isTrait();
    }

    public function isAbstract(): bool
    {
        return $this->betterReflectionEnum->isAbstract();
    }

    public function isFinal(): bool
    {
        return $this->betterReflectionEnum->isFinal();
    }

    public function isReadOnly(): bool
    {
        return $this->betterReflectionEnum->isReadOnly();
    }

    public function getModifiers(): int
    {
        return $this->betterReflectionEnum->getModifiers();
    }

    public function isInstance(object $object): bool
    {
        return $this->betterReflectionEnum->isInstance($object);
    }

    public function newInstance(mixed ...$args): object
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    public function newInstanceWithoutConstructor(): object
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    public function newInstanceArgs(array|null $args = null): object
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    public function getParentClass(): ReflectionClass|false
    {
        return false;
    }

    public function isSubclassOf(CoreReflectionClass|string $class): bool
    {
        $realParentClassNames = $this->betterReflectionEnum->getParentClassNames();

        $parentClassNames = array_combine(array_map(static fn (string $parentClassName): string => strtolower($parentClassName), $realParentClassNames), $realParentClassNames);

        $className           = $class instanceof CoreReflectionClass ? $class->getName() : $class;
        $lowercasedClassName = strtolower($className);

        $realParentClassName = $parentClassNames[$lowercasedClassName] ?? $className;

        if ($this->betterReflectionEnum->isSubclassOf($realParentClassName)) {
            return true;
        }

        return $this->implementsInterface($className);
    }

    /**
     * @return array<string, mixed>
     *
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function getStaticProperties(): array
    {
        return $this->betterReflectionEnum->getStaticProperties();
    }

    public function getStaticPropertyValue(string $name, mixed $default = null): mixed
    {
        throw new CoreReflectionException(sprintf('Property %s::$%s does not exist', $this->betterReflectionEnum->getName(), $name));
    }

    public function setStaticPropertyValue(string $name, mixed $value): void
    {
        throw new CoreReflectionException(sprintf('Class %s does not have a property named %s', $this->betterReflectionEnum->getName(), $name));
    }

    /** @return array<non-empty-string, mixed> */
    public function getDefaultProperties(): array
    {
        return $this->betterReflectionEnum->getDefaultProperties();
    }

    public function isIterateable(): bool
    {
        return $this->betterReflectionEnum->isIterateable();
    }

    public function isIterable(): bool
    {
        return $this->isIterateable();
    }

    public function implementsInterface(CoreReflectionClass|string $interface): bool
    {
        $realInterfaceNames = $this->betterReflectionEnum->getInterfaceNames();

        $interfaceNames = array_combine(array_map(static fn (string $interfaceName): string => strtolower($interfaceName), $realInterfaceNames), $realInterfaceNames);

        $interfaceName           = $interface instanceof CoreReflectionClass ? $interface->getName() : $interface;
        $lowercasedInterfaceName = strtolower($interfaceName);

        $realInterfaceName = $interfaceNames[$lowercasedInterfaceName] ?? $interfaceName;

        return $this->betterReflectionEnum->implementsInterface($realInterfaceName);
    }

    public function getExtension(): CoreReflectionExtension|null
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /** @return non-empty-string|false */
    public function getExtensionName(): string|false
    {
        return $this->betterReflectionEnum->getExtensionName() ?? false;
    }

    public function inNamespace(): bool
    {
        return $this->betterReflectionEnum->inNamespace();
    }

    public function getNamespaceName(): string
    {
        return $this->betterReflectionEnum->getNamespaceName() ?? '';
    }

    public function getShortName(): string
    {
        return $this->betterReflectionEnum->getShortName();
    }

    /**
     * @param class-string|null $name
     *
     * @return list<ReflectionAttribute>
     */
    public function getAttributes(string|null $name = null, int $flags = 0): array
    {
        if ($flags !== 0 && $flags !== ReflectionAttribute::IS_INSTANCEOF) {
            throw new ValueError('Argument #2 ($flags) must be a valid attribute filter flag');
        }

        if ($name !== null && $flags & ReflectionAttribute::IS_INSTANCEOF) {
            $attributes = $this->betterReflectionEnum->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionEnum->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionEnum->getAttributes();
        }

        /** @psalm-suppress ImpureFunctionCall */
        return array_map(static fn (BetterReflectionAttribute $betterReflectionAttribute): ReflectionAttribute => new ReflectionAttribute($betterReflectionAttribute), $attributes);
    }

    public function isEnum(): bool
    {
        return $this->betterReflectionEnum->isEnum();
    }

    public function hasCase(string $name): bool
    {
        if ($name === '') {
            return false;
        }

        return $this->betterReflectionEnum->hasCase($name);
    }

    public function getCase(string $name): ReflectionEnumUnitCase|ReflectionEnumBackedCase
    {
        $case = $name !== '' ? $this->betterReflectionEnum->getCase($name) : null;

        if ($case === null) {
            throw new CoreReflectionException(sprintf('Case %s::%s does not exist', $this->betterReflectionEnum->getName(), $name));
        }

        if ($this->betterReflectionEnum->isBacked()) {
            return new ReflectionEnumBackedCase($case);
        }

        return new ReflectionEnumUnitCase($case);
    }

    /** @return list<ReflectionEnumUnitCase|ReflectionEnumBackedCase> */
    public function getCases(): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_map(function (BetterReflectionEnumCase $case): ReflectionEnumUnitCase|ReflectionEnumBackedCase {
            if ($this->betterReflectionEnum->isBacked()) {
                return new ReflectionEnumBackedCase($case);
            }

            return new ReflectionEnumUnitCase($case);
        }, array_values($this->betterReflectionEnum->getCases()));
    }

    public function isBacked(): bool
    {
        return $this->betterReflectionEnum->isBacked();
    }

    public function getBackingType(): ReflectionNamedType|null
    {
        if ($this->betterReflectionEnum->isBacked()) {
            return new ReflectionNamedType($this->betterReflectionEnum->getBackingType(), false);
        }

        return null;
    }
}
