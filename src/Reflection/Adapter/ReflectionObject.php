<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use OutOfBoundsException;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionExtension as CoreReflectionExtension;
use ReflectionObject as CoreReflectionObject;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionObject as BetterReflectionObject;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use Roave\BetterReflection\Util\FileHelper;
use ValueError;

use function array_combine;
use function array_map;
use function array_values;
use function func_num_args;
use function sprintf;
use function strtolower;

/** @psalm-suppress PropertyNotSetInConstructor */
final class ReflectionObject extends CoreReflectionObject
{
    public function __construct(private BetterReflectionObject $betterReflectionObject)
    {
        unset($this->name);
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->betterReflectionObject->__toString();
    }

    /** @psalm-mutation-free */
    public function getName(): string
    {
        return $this->betterReflectionObject->getName();
    }

    /** @psalm-mutation-free */
    public function isInternal(): bool
    {
        return $this->betterReflectionObject->isInternal();
    }

    /** @psalm-mutation-free */
    public function isUserDefined(): bool
    {
        return $this->betterReflectionObject->isUserDefined();
    }

    /** @psalm-mutation-free */
    public function isInstantiable(): bool
    {
        return $this->betterReflectionObject->isInstantiable();
    }

    /** @psalm-mutation-free */
    public function isCloneable(): bool
    {
        return $this->betterReflectionObject->isCloneable();
    }

    /**
     * @return non-empty-string|false
     *
     * @psalm-mutation-free
     */
    public function getFileName(): string|false
    {
        $fileName = $this->betterReflectionObject->getFileName();

        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : false;
    }

    /** @psalm-mutation-free */
    public function getStartLine(): int|false
    {
        return $this->betterReflectionObject->getStartLine();
    }

    /** @psalm-mutation-free */
    public function getEndLine(): int|false
    {
        return $this->betterReflectionObject->getEndLine();
    }

    /** @psalm-mutation-free */
    public function getDocComment(): string|false
    {
        return $this->betterReflectionObject->getDocComment() ?? false;
    }

    /** @psalm-mutation-free */
    public function getConstructor(): ReflectionMethod|null
    {
        $constructor = $this->betterReflectionObject->getConstructor();

        if ($constructor === null) {
            return null;
        }

        return new ReflectionMethod($constructor);
    }

    /** @psalm-mutation-free */
    public function hasMethod(string $name): bool
    {
        if ($name === '') {
            return false;
        }

        return $this->betterReflectionObject->hasMethod($this->getMethodRealName($name));
    }

    /** @psalm-mutation-free */
    public function getMethod(string $name): ReflectionMethod
    {
        $method = $name !== '' ? $this->betterReflectionObject->getMethod($this->getMethodRealName($name)) : null;

        if ($method === null) {
            throw new CoreReflectionException(sprintf('Method %s::%s() does not exist', $this->betterReflectionObject->getName(), $name));
        }

        return new ReflectionMethod($method);
    }

    /**
     * @param non-empty-string $name
     *
     * @return non-empty-string
     *
     * @psalm-mutation-free
     */
    private function getMethodRealName(string $name): string
    {
        $realMethodNames = array_map(static fn (BetterReflectionMethod $method): string => $method->getName(), $this->betterReflectionObject->getMethods());

        $methodNames = array_combine(array_map(static fn (string $methodName): string => strtolower($methodName), $realMethodNames), $realMethodNames);

        $lowercasedName = strtolower($name);

        return $methodNames[$lowercasedName] ?? $name;
    }

    /**
     * @param int-mask-of<ReflectionMethod::IS_*>|null $filter
     *
     * @return list<ReflectionMethod>
     *
     * @psalm-mutation-free
     */
    public function getMethods(int|null $filter = null): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_values(array_map(
            static fn (BetterReflectionMethod $method): ReflectionMethod => new ReflectionMethod($method),
            $this->betterReflectionObject->getMethods($filter ?? 0),
        ));
    }

    /** @psalm-mutation-free */
    public function hasProperty(string $name): bool
    {
        if ($name === '') {
            return false;
        }

        return $this->betterReflectionObject->hasProperty($name);
    }

    /** @psalm-mutation-free */
    public function getProperty(string $name): ReflectionProperty
    {
        $property = $name !== '' ? $this->betterReflectionObject->getProperty($name) : null;

        if ($property === null) {
            throw new CoreReflectionException(sprintf('Property %s::$%s does not exist', $this->betterReflectionObject->getName(), $name));
        }

        return new ReflectionProperty($property);
    }

    /**
     * @param int-mask-of<ReflectionProperty::IS_*>|null $filter
     *
     * @return list<ReflectionProperty>
     *
     * @psalm-mutation-free
     */
    public function getProperties(int|null $filter = null): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_values(array_map(
            static fn (BetterReflectionProperty $property): ReflectionProperty => new ReflectionProperty($property),
            $this->betterReflectionObject->getProperties($filter ?? 0),
        ));
    }

    /** @psalm-mutation-free */
    public function hasConstant(string $name): bool
    {
        if ($name === '') {
            return false;
        }

        return $this->betterReflectionObject->hasConstant($name);
    }

    /**
     * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
     *
     * @return array<non-empty-string, mixed>
     *
     * @psalm-mutation-free
     */
    public function getConstants(int|null $filter = null): array
    {
        return array_map(
            static fn (BetterReflectionClassConstant $betterConstant): mixed => $betterConstant->getValue(),
            $this->betterReflectionObject->getConstants($filter ?? 0),
        );
    }

    /** @psalm-mutation-free */
    public function getConstant(string $name): mixed
    {
        if ($name === '') {
            return false;
        }

        $betterReflectionConstant = $this->betterReflectionObject->getConstant($name);
        if ($betterReflectionConstant === null) {
            return false;
        }

        return $betterReflectionConstant->getValue();
    }

    /** @psalm-mutation-free */
    public function getReflectionConstant(string $name): ReflectionClassConstant|false
    {
        if ($name === '') {
            return false;
        }

        $betterReflectionConstant = $this->betterReflectionObject->getConstant($name);

        if ($betterReflectionConstant === null) {
            return false;
        }

        return new ReflectionClassConstant($betterReflectionConstant);
    }

    /**
     * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
     *
     * @return list<ReflectionClassConstant>
     *
     * @psalm-mutation-free
     */
    public function getReflectionConstants(int|null $filter = null): array
    {
        return array_values(array_map(
            static fn (BetterReflectionClassConstant $betterConstant): ReflectionClassConstant => new ReflectionClassConstant($betterConstant),
            $this->betterReflectionObject->getConstants($filter ?? 0),
        ));
    }

    /**
     * @return array<class-string, CoreReflectionClass>
     *
     * @psalm-mutation-free
     */
    public function getInterfaces(): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_map(
            static fn (BetterReflectionClass $interface): ReflectionClass => new ReflectionClass($interface),
            $this->betterReflectionObject->getInterfaces(),
        );
    }

    /**
     * @return list<class-string>
     *
     * @psalm-mutation-free
     */
    public function getInterfaceNames(): array
    {
        return $this->betterReflectionObject->getInterfaceNames();
    }

    /** @psalm-mutation-free */
    public function isInterface(): bool
    {
        return $this->betterReflectionObject->isInterface();
    }

    /**
     * @return array<trait-string, ReflectionClass>
     *
     * @psalm-mutation-free
     */
    public function getTraits(): array
    {
        $traits = $this->betterReflectionObject->getTraits();

        /** @var list<trait-string> $traitNames */
        $traitNames = array_map(static fn (BetterReflectionClass $trait): string => $trait->getName(), $traits);

        /** @psalm-suppress ImpureFunctionCall */
        return array_combine(
            $traitNames,
            array_map(static fn (BetterReflectionClass $trait): ReflectionClass => new ReflectionClass($trait), $traits),
        );
    }

    /**
     * @return list<trait-string>
     *
     * @psalm-mutation-free
     */
    public function getTraitNames(): array
    {
        return $this->betterReflectionObject->getTraitNames();
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     *
     * @psalm-mutation-free
     */
    public function getTraitAliases(): array
    {
        return $this->betterReflectionObject->getTraitAliases();
    }

    /** @psalm-mutation-free */
    public function isTrait(): bool
    {
        return $this->betterReflectionObject->isTrait();
    }

    /** @psalm-mutation-free */
    public function isAbstract(): bool
    {
        return $this->betterReflectionObject->isAbstract();
    }

    /** @psalm-mutation-free */
    public function isFinal(): bool
    {
        return $this->betterReflectionObject->isFinal();
    }

    /** @psalm-mutation-free */
    public function isReadOnly(): bool
    {
        return $this->betterReflectionObject->isReadOnly();
    }

    /** @psalm-mutation-free */
    public function getModifiers(): int
    {
        return $this->betterReflectionObject->getModifiers();
    }

    /** @psalm-mutation-free */
    public function isInstance(object $object): bool
    {
        return $this->betterReflectionObject->isInstance($object);
    }

    public function newInstance(mixed ...$args): ReflectionObject
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    public function newInstanceWithoutConstructor(): ReflectionObject
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    public function newInstanceArgs(array|null $args = null): ReflectionObject
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /** @psalm-mutation-free */
    public function getParentClass(): ReflectionClass|false
    {
        $parentClass = $this->betterReflectionObject->getParentClass();

        if ($parentClass === null) {
            return false;
        }

        return new ReflectionClass($parentClass);
    }

    /** @psalm-mutation-free */
    public function isSubclassOf(CoreReflectionClass|string $class): bool
    {
        $realParentClassNames = $this->betterReflectionObject->getParentClassNames();

        $parentClassNames = array_combine(array_map(static fn (string $parentClassName): string => strtolower($parentClassName), $realParentClassNames), $realParentClassNames);

        $className           = $class instanceof CoreReflectionClass ? $class->getName() : $class;
        $lowercasedClassName = strtolower($className);

        $realParentClassName = $parentClassNames[$lowercasedClassName] ?? $className;

        return $this->betterReflectionObject->isSubclassOf($realParentClassName);
    }

    /**
     * @return array<string, mixed>
     *
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function getStaticProperties(): array
    {
        return $this->betterReflectionObject->getStaticProperties();
    }

    public function getStaticPropertyValue(string $name, mixed $default = null): mixed
    {
        $betterReflectionProperty = $name !== '' ? $this->betterReflectionObject->getProperty($name) : null;

        if ($betterReflectionProperty === null) {
            if (func_num_args() === 2) {
                return $default;
            }

            throw new CoreReflectionException(sprintf('Property %s::$%s does not exist', $this->betterReflectionObject->getName(), $name));
        }

        $property = new ReflectionProperty($betterReflectionProperty);

        if (! $property->isStatic()) {
            throw new CoreReflectionException(sprintf('Property %s::$%s does not exist', $this->betterReflectionObject->getName(), $name));
        }

        return $property->getValue();
    }

    public function setStaticPropertyValue(string $name, mixed $value): void
    {
        $betterReflectionProperty = $name !== '' ? $this->betterReflectionObject->getProperty($name) : null;

        if ($betterReflectionProperty === null) {
            throw new CoreReflectionException(sprintf('Class %s does not have a property named %s', $this->betterReflectionObject->getName(), $name));
        }

        $property = new ReflectionProperty($betterReflectionProperty);

        if (! $property->isStatic()) {
            throw new CoreReflectionException(sprintf('Class %s does not have a property named %s', $this->betterReflectionObject->getName(), $name));
        }

        $property->setValue($value);
    }

    /**
     * @return array<non-empty-string, mixed>
     *
     * @psalm-mutation-free
     */
    public function getDefaultProperties(): array
    {
        return $this->betterReflectionObject->getDefaultProperties();
    }

    /** @psalm-mutation-free */
    public function isIterateable(): bool
    {
        return $this->betterReflectionObject->isIterateable();
    }

    /** @psalm-mutation-free */
    public function isIterable(): bool
    {
        return $this->isIterateable();
    }

    /** @psalm-mutation-free */
    public function implementsInterface(CoreReflectionClass|string $interface): bool
    {
        $realInterfaceNames = $this->betterReflectionObject->getInterfaceNames();

        $interfaceNames = array_combine(array_map(static fn (string $interfaceName): string => strtolower($interfaceName), $realInterfaceNames), $realInterfaceNames);

        $interfaceName           = $interface instanceof CoreReflectionClass ? $interface->getName() : $interface;
        $lowercasedInterfaceName = strtolower($interfaceName);

        $realInterfaceName = $interfaceNames[$lowercasedInterfaceName] ?? $interfaceName;

        return $this->betterReflectionObject->implementsInterface($realInterfaceName);
    }

    /** @psalm-mutation-free */
    public function getExtension(): CoreReflectionExtension|null
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @return non-empty-string|false
     *
     * @psalm-mutation-free
     */
    public function getExtensionName(): string|false
    {
        return $this->betterReflectionObject->getExtensionName() ?? false;
    }

    /** @psalm-mutation-free */
    public function inNamespace(): bool
    {
        return $this->betterReflectionObject->inNamespace();
    }

    /** @psalm-mutation-free */
    public function getNamespaceName(): string
    {
        return $this->betterReflectionObject->getNamespaceName() ?? '';
    }

    /** @psalm-mutation-free */
    public function getShortName(): string
    {
        return $this->betterReflectionObject->getShortName();
    }

    /** @psalm-mutation-free */
    public function isAnonymous(): bool
    {
        return $this->betterReflectionObject->isAnonymous();
    }

    /**
     * @param class-string|null $name
     *
     * @return list<ReflectionAttribute>
     *
     * @psalm-mutation-free
     */
    public function getAttributes(string|null $name = null, int $flags = 0): array
    {
        if ($flags !== 0 && $flags !== ReflectionAttribute::IS_INSTANCEOF) {
            throw new ValueError('Argument #2 ($flags) must be a valid attribute filter flag');
        }

        if ($name !== null && $flags & ReflectionAttribute::IS_INSTANCEOF) {
            $attributes = $this->betterReflectionObject->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionObject->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionObject->getAttributes();
        }

        /** @psalm-suppress ImpureFunctionCall */
        return array_map(static fn (BetterReflectionAttribute $betterReflectionAttribute): ReflectionAttribute => new ReflectionAttribute($betterReflectionAttribute), $attributes);
    }

    /** @psalm-mutation-free */
    public function isEnum(): bool
    {
        return $this->betterReflectionObject->isEnum();
    }

    public function __get(string $name): mixed
    {
        if ($name === 'name') {
            return $this->betterReflectionObject->getName();
        }

        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }
}
