<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use OutOfBoundsException;
use ReflectionClass as CoreReflectionClass;
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
use function array_filter;
use function array_map;
use function array_values;
use function constant;
use function func_num_args;
use function sprintf;
use function strtolower;

/** @psalm-suppress PropertyNotSetInConstructor */
final class ReflectionClass extends CoreReflectionClass
{
    public function __construct(private BetterReflectionClass|BetterReflectionEnum $betterReflectionClass)
    {
        unset($this->name);
    }

    public function __toString(): string
    {
        return $this->betterReflectionClass->__toString();
    }

    public function __get(string $name): mixed
    {
        if ($name === 'name') {
            return $this->betterReflectionClass->getName();
        }

        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }

    public function getName(): string
    {
        return $this->betterReflectionClass->getName();
    }

    public function isAnonymous(): bool
    {
        return $this->betterReflectionClass->isAnonymous();
    }

    public function isInternal(): bool
    {
        return $this->betterReflectionClass->isInternal();
    }

    public function isUserDefined(): bool
    {
        return $this->betterReflectionClass->isUserDefined();
    }

    public function isInstantiable(): bool
    {
        return $this->betterReflectionClass->isInstantiable();
    }

    public function isCloneable(): bool
    {
        return $this->betterReflectionClass->isCloneable();
    }

    public function getFileName(): string|false
    {
        $fileName = $this->betterReflectionClass->getFileName();

        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : false;
    }

    public function getStartLine(): int|false
    {
        return $this->betterReflectionClass->getStartLine();
    }

    public function getEndLine(): int|false
    {
        return $this->betterReflectionClass->getEndLine();
    }

    public function getDocComment(): string|false
    {
        return $this->betterReflectionClass->getDocComment() ?: false;
    }

    public function getConstructor(): CoreReflectionMethod|null
    {
        try {
            return new ReflectionMethod($this->betterReflectionClass->getConstructor());
        } catch (OutOfBoundsException) {
            return null;
        }
    }

    public function hasMethod(string $name): bool
    {
        return $this->betterReflectionClass->hasMethod($name);
    }

    public function getMethod(string $name): ReflectionMethod
    {
        return new ReflectionMethod($this->betterReflectionClass->getMethod($name));
    }

    /**
     * @return list<ReflectionMethod>
     *
     * @psalm-suppress MethodSignatureMismatch
     */
    public function getMethods(int|null $filter = null): array
    {
        return array_map(static fn (BetterReflectionMethod $method): ReflectionMethod => new ReflectionMethod($method), $this->betterReflectionClass->getMethods($filter));
    }

    public function hasProperty(string $name): bool
    {
        return $this->betterReflectionClass->hasProperty($name);
    }

    public function getProperty(string $name): ReflectionProperty
    {
        $betterReflectionProperty = $this->betterReflectionClass->getProperty($name);

        if ($betterReflectionProperty === null) {
            throw new CoreReflectionException(sprintf('Property "%s" does not exist', $name));
        }

        return new ReflectionProperty($betterReflectionProperty);
    }

    /**
     * @return list<ReflectionProperty>
     *
     * @psalm-suppress MethodSignatureMismatch
     */
    public function getProperties(int|null $filter = null): array
    {
        return array_values(array_map(static fn (BetterReflectionProperty $property): ReflectionProperty => new ReflectionProperty($property), $this->betterReflectionClass->getProperties($filter)));
    }

    public function hasConstant(string $name): bool
    {
        if ($this->betterReflectionClass instanceof BetterReflectionEnum && $this->betterReflectionClass->hasCase($name)) {
            return true;
        }

        return $this->betterReflectionClass->hasConstant($name);
    }

    /** @return array<string, mixed> */
    public function getConstants(int|null $filter = null): array
    {
        return array_map(
            fn (BetterReflectionClassConstant|BetterReflectionEnumCase $betterConstantOrEnumCase): mixed => $this->getConstantValue($betterConstantOrEnumCase),
            $this->filterBetterReflectionClassConstants($filter),
        );
    }

    public function getConstant(string $name): mixed
    {
        if ($this->betterReflectionClass instanceof BetterReflectionEnum) {
            $enumCase = $this->betterReflectionClass->getCase($name);
            if ($enumCase !== null) {
                return $this->getConstantValue($enumCase);
            }
        }

        return $this->betterReflectionClass->getConstant($name);
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
        if ($this->betterReflectionClass instanceof BetterReflectionEnum) {
            $enumCase = $this->betterReflectionClass->getCase($name);
            if ($enumCase !== null) {
                return new ReflectionClassConstant($enumCase);
            }
        }

        $betterReflectionConstant = $this->betterReflectionClass->getReflectionConstant($name);
        if ($betterReflectionConstant === null) {
            return false;
        }

        return new ReflectionClassConstant($betterReflectionConstant);
    }

    /** @return list<ReflectionClassConstant> */
    public function getReflectionConstants(int|null $filter = null): array
    {
        return array_values(array_map(
            static fn (BetterReflectionClassConstant|BetterReflectionEnumCase $betterConstantOrEnum): ReflectionClassConstant => new ReflectionClassConstant($betterConstantOrEnum),
            $this->filterBetterReflectionClassConstants($filter),
        ));
    }

    /** @return array<string, BetterReflectionClassConstant|BetterReflectionEnumCase> */
    private function filterBetterReflectionClassConstants(int|null $filter): array
    {
        $reflectionConstants = $this->betterReflectionClass->getReflectionConstants();

        if ($filter !== null) {
            $reflectionConstants = array_filter(
                $this->betterReflectionClass->getReflectionConstants(),
                static fn (BetterReflectionClassConstant $betterConstant): bool => (bool) ($betterConstant->getModifiers() & $filter),
            );
        }

        if (
            $this->betterReflectionClass instanceof BetterReflectionEnum
            && (
                $filter === null
                || $filter & ReflectionClassConstant::IS_PUBLIC
            )
        ) {
            $reflectionConstants += $this->betterReflectionClass->getCases();
        }

        return $reflectionConstants;
    }

    /** @return array<class-string, CoreReflectionClass> */
    public function getInterfaces(): array
    {
        return array_map(
            static fn (BetterReflectionClass $interface): self => new self($interface),
            $this->betterReflectionClass->getInterfaces(),
        );
    }

    /** @return list<class-string> */
    public function getInterfaceNames(): array
    {
        return $this->betterReflectionClass->getInterfaceNames();
    }

    public function isInterface(): bool
    {
        return $this->betterReflectionClass->isInterface();
    }

    /** @return array<trait-string, CoreReflectionClass> */
    public function getTraits(): array
    {
        $traits = $this->betterReflectionClass->getTraits();

        /** @var list<trait-string> $traitNames */
        $traitNames = array_map(static fn (BetterReflectionClass $trait): string => $trait->getName(), $traits);

        return array_combine(
            $traitNames,
            array_map(static fn (BetterReflectionClass $trait): self => new self($trait), $traits),
        );
    }

    /** @return list<trait-string> */
    public function getTraitNames(): array
    {
        return $this->betterReflectionClass->getTraitNames();
    }

    /** @return array<string, string> */
    public function getTraitAliases(): array
    {
        return $this->betterReflectionClass->getTraitAliases();
    }

    public function isTrait(): bool
    {
        return $this->betterReflectionClass->isTrait();
    }

    public function isAbstract(): bool
    {
        return $this->betterReflectionClass->isAbstract();
    }

    public function isFinal(): bool
    {
        return $this->betterReflectionClass->isFinal();
    }

    public function isReadOnly(): bool
    {
        return $this->betterReflectionClass->isReadOnly();
    }

    public function getModifiers(): int
    {
        return $this->betterReflectionClass->getModifiers();
    }

    public function isInstance(object $object): bool
    {
        return $this->betterReflectionClass->isInstance($object);
    }

    public function newInstance(mixed ...$args): self
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
        $parentClass = $this->betterReflectionClass->getParentClass();

        if ($parentClass === null) {
            return false;
        }

        return new self($parentClass);
    }

    /** @psalm-suppress MethodSignatureMismatch */
    public function isSubclassOf(CoreReflectionClass|string $class): bool
    {
        $realParentClassNames = $this->betterReflectionClass->getParentClassNames();

        $parentClassNames = array_combine(array_map(static fn (string $parentClassName): string => strtolower($parentClassName), $realParentClassNames), $realParentClassNames);

        $className           = $class instanceof CoreReflectionClass ? $class->getName() : $class;
        $lowercasedClassName = strtolower($className);

        $realParentClassName = $parentClassNames[$lowercasedClassName] ?? $className;

        return $this->betterReflectionClass->isSubclassOf($realParentClassName) || $this->implementsInterface($className);
    }

    /**
     * @return array<string, mixed>
     *
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function getStaticProperties(): array
    {
        return $this->betterReflectionClass->getStaticProperties();
    }

    public function getStaticPropertyValue(string $name, mixed $default = null): mixed
    {
        $betterReflectionProperty = $this->betterReflectionClass->getProperty($name);

        if ($betterReflectionProperty === null) {
            if (func_num_args() === 2) {
                return $default;
            }

            throw new CoreReflectionException(sprintf('Property "%s" does not exist', $name));
        }

        $property = new ReflectionProperty($betterReflectionProperty);

        if (! $property->isStatic()) {
            throw new CoreReflectionException(sprintf('Property "%s" is not static', $name));
        }

        return $property->getValue();
    }

    public function setStaticPropertyValue(string $name, mixed $value): void
    {
        $betterReflectionProperty = $this->betterReflectionClass->getProperty($name);

        if ($betterReflectionProperty === null) {
            throw new CoreReflectionException(sprintf('Property "%s" does not exist', $name));
        }

        $property = new ReflectionProperty($betterReflectionProperty);

        if (! $property->isStatic()) {
            throw new CoreReflectionException(sprintf('Property "%s" is not static', $name));
        }

        $property->setValue($value);
    }

    /** @return array<string, scalar|array<scalar>|null> */
    public function getDefaultProperties(): array
    {
        return $this->betterReflectionClass->getDefaultProperties();
    }

    public function isIterateable(): bool
    {
        return $this->betterReflectionClass->isIterateable();
    }

    public function isIterable(): bool
    {
        return $this->isIterateable();
    }

    /** @psalm-suppress MethodSignatureMismatch */
    public function implementsInterface(CoreReflectionClass|string $interface): bool
    {
        $realInterfaceNames = $this->betterReflectionClass->getInterfaceNames();

        $interfaceNames = array_combine(array_map(static fn (string $interfaceName): string => strtolower($interfaceName), $realInterfaceNames), $realInterfaceNames);

        $interfaceName          = $interface instanceof CoreReflectionClass ? $interface->getName() : $interface;
        $lowercasedIntefaceName = strtolower($interfaceName);

        $realInterfaceName = $interfaceNames[$lowercasedIntefaceName] ?? $interfaceName;

        return $this->betterReflectionClass->implementsInterface($realInterfaceName);
    }

    public function getExtension(): CoreReflectionExtension|null
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    public function getExtensionName(): string|false
    {
        return $this->betterReflectionClass->getExtensionName() ?? false;
    }

    public function inNamespace(): bool
    {
        return $this->betterReflectionClass->inNamespace();
    }

    public function getNamespaceName(): string
    {
        return $this->betterReflectionClass->getNamespaceName();
    }

    public function getShortName(): string
    {
        return $this->betterReflectionClass->getShortName();
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
            $attributes = $this->betterReflectionClass->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionClass->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionClass->getAttributes();
        }

        return array_map(static fn (BetterReflectionAttribute $betterReflectionAttribute): ReflectionAttribute => new ReflectionAttribute($betterReflectionAttribute), $attributes);
    }

    public function isEnum(): bool
    {
        return $this->betterReflectionClass->isEnum();
    }
}
