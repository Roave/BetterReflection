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
use function array_filter;
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

    public function __toString(): string
    {
        return $this->betterReflectionObject->__toString();
    }

    public function getName(): string
    {
        return $this->betterReflectionObject->getName();
    }

    public function isInternal(): bool
    {
        return $this->betterReflectionObject->isInternal();
    }

    public function isUserDefined(): bool
    {
        return $this->betterReflectionObject->isUserDefined();
    }

    public function isInstantiable(): bool
    {
        return $this->betterReflectionObject->isInstantiable();
    }

    public function isCloneable(): bool
    {
        return $this->betterReflectionObject->isCloneable();
    }

    public function getFileName(): string|false
    {
        $fileName = $this->betterReflectionObject->getFileName();

        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : false;
    }

    public function getStartLine(): int|false
    {
        return $this->betterReflectionObject->getStartLine();
    }

    public function getEndLine(): int|false
    {
        return $this->betterReflectionObject->getEndLine();
    }

    public function getDocComment(): string|false
    {
        return $this->betterReflectionObject->getDocComment() ?: false;
    }

    public function getConstructor(): ReflectionMethod
    {
        return new ReflectionMethod($this->betterReflectionObject->getConstructor());
    }

    public function hasMethod(string $name): bool
    {
        return $this->betterReflectionObject->hasMethod($this->getMethodRealName($name));
    }

    public function getMethod(string $name): ReflectionMethod
    {
        return new ReflectionMethod($this->betterReflectionObject->getMethod($this->getMethodRealName($name)));
    }

    private function getMethodRealName(string $name): string
    {
        $realMethodNames = array_map(static fn (BetterReflectionMethod $method): string => $method->getName(), $this->betterReflectionObject->getMethods());

        $methodNames = array_combine(array_map(static fn (string $methodName): string => strtolower($methodName), $realMethodNames), $realMethodNames);

        return $methodNames[strtolower($name)] ?? $name;
    }

    /**
     * @return list<ReflectionMethod>
     *
     * @psalm-suppress MethodSignatureMismatch
     */
    public function getMethods(int|null $filter = null): array
    {
        return array_map(
            static fn (BetterReflectionMethod $method): ReflectionMethod => new ReflectionMethod($method),
            $this->betterReflectionObject->getMethods($filter),
        );
    }

    public function hasProperty(string $name): bool
    {
        return $this->betterReflectionObject->hasProperty($name);
    }

    public function getProperty(string $name): ReflectionProperty
    {
        $property = $this->betterReflectionObject->getProperty($name);

        if ($property === null) {
            throw new CoreReflectionException(sprintf('Property "%s" does not exist', $name));
        }

        return new ReflectionProperty($property);
    }

    /**
     * @return list<ReflectionProperty>
     *
     * @psalm-suppress MethodSignatureMismatch
     */
    public function getProperties(int|null $filter = null): array
    {
        return array_values(array_map(static fn (BetterReflectionProperty $property): ReflectionProperty => new ReflectionProperty($property), $this->betterReflectionObject->getProperties()));
    }

    public function hasConstant(string $name): bool
    {
        return $this->betterReflectionObject->hasConstant($name);
    }

    /** @return array<string, mixed> */
    public function getConstants(int|null $filter = null): array
    {
        $reflectionConstants = $this->betterReflectionObject->getReflectionConstants();

        if ($filter !== null) {
            $reflectionConstants = array_filter(
                $reflectionConstants,
                static fn (BetterReflectionClassConstant $betterConstant): bool => (bool) ($betterConstant->getModifiers() & $filter),
            );
        }

        return array_map(
            static fn (BetterReflectionClassConstant $betterConstant): mixed => $betterConstant->getValue(),
            $reflectionConstants,
        );
    }

    public function getConstant(string $name): mixed
    {
        return $this->betterReflectionObject->getConstant($name);
    }

    public function getReflectionConstant(string $name): ReflectionClassConstant|false
    {
        $betterReflectionConstant = $this->betterReflectionObject->getReflectionConstant($name);

        if ($betterReflectionConstant === null) {
            return false;
        }

        return new ReflectionClassConstant($betterReflectionConstant);
    }

    /** @return list<ReflectionClassConstant> */
    public function getReflectionConstants(int|null $filter = null): array
    {
        return array_values(array_map(
            static fn (BetterReflectionClassConstant $betterConstant): ReflectionClassConstant => new ReflectionClassConstant($betterConstant),
            $this->betterReflectionObject->getReflectionConstants(),
        ));
    }

    /** @return array<class-string, CoreReflectionClass> */
    public function getInterfaces(): array
    {
        return array_map(
            static fn (BetterReflectionClass $interface): ReflectionClass => new ReflectionClass($interface),
            $this->betterReflectionObject->getInterfaces(),
        );
    }

    /** @return list<class-string> */
    public function getInterfaceNames(): array
    {
        return $this->betterReflectionObject->getInterfaceNames();
    }

    public function isInterface(): bool
    {
        return $this->betterReflectionObject->isInterface();
    }

    /** @return array<trait-string, ReflectionClass> */
    public function getTraits(): array
    {
        $traits = $this->betterReflectionObject->getTraits();

        /** @var list<trait-string> $traitNames */
        $traitNames = array_map(static fn (BetterReflectionClass $trait): string => $trait->getName(), $traits);

        return array_combine(
            $traitNames,
            array_map(static fn (BetterReflectionClass $trait): ReflectionClass => new ReflectionClass($trait), $traits),
        );
    }

    /** @return list<trait-string> */
    public function getTraitNames(): array
    {
        return $this->betterReflectionObject->getTraitNames();
    }

    /** @return array<string, string> */
    public function getTraitAliases(): array
    {
        return $this->betterReflectionObject->getTraitAliases();
    }

    public function isTrait(): bool
    {
        return $this->betterReflectionObject->isTrait();
    }

    public function isAbstract(): bool
    {
        return $this->betterReflectionObject->isAbstract();
    }

    public function isFinal(): bool
    {
        return $this->betterReflectionObject->isFinal();
    }

    public function isReadOnly(): bool
    {
        return $this->betterReflectionObject->isReadOnly();
    }

    public function getModifiers(): int
    {
        return $this->betterReflectionObject->getModifiers();
    }

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

    public function getParentClass(): ReflectionClass|false
    {
        $parentClass = $this->betterReflectionObject->getParentClass();

        if ($parentClass === null) {
            return false;
        }

        return new ReflectionClass($parentClass);
    }

    /** @psalm-suppress MethodSignatureMismatch */
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
        $betterReflectionProperty = $this->betterReflectionObject->getProperty($name);

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
        $betterReflectionProperty = $this->betterReflectionObject->getProperty($name);

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
        return $this->betterReflectionObject->getDefaultProperties();
    }

    public function isIterateable(): bool
    {
        return $this->betterReflectionObject->isIterateable();
    }

    public function isIterable(): bool
    {
        return $this->isIterateable();
    }

    /** @psalm-suppress MethodSignatureMismatch */
    public function implementsInterface(CoreReflectionClass|string $interface): bool
    {
        $realInterfaceNames = $this->betterReflectionObject->getInterfaceNames();

        $interfaceNames = array_combine(array_map(static fn (string $interfaceName): string => strtolower($interfaceName), $realInterfaceNames), $realInterfaceNames);

        $interfaceName          = $interface instanceof CoreReflectionClass ? $interface->getName() : $interface;
        $lowercasedIntefaceName = strtolower($interfaceName);

        $realInterfaceName = $interfaceNames[$lowercasedIntefaceName] ?? $interfaceName;

        return $this->betterReflectionObject->implementsInterface($realInterfaceName);
    }

    public function getExtension(): CoreReflectionExtension|null
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    public function getExtensionName(): string|false
    {
        return $this->betterReflectionObject->getExtensionName() ?? false;
    }

    public function inNamespace(): bool
    {
        return $this->betterReflectionObject->inNamespace();
    }

    public function getNamespaceName(): string
    {
        return $this->betterReflectionObject->getNamespaceName();
    }

    public function getShortName(): string
    {
        return $this->betterReflectionObject->getShortName();
    }

    public function isAnonymous(): bool
    {
        return $this->betterReflectionObject->isAnonymous();
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
            $attributes = $this->betterReflectionObject->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionObject->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionObject->getAttributes();
        }

        return array_map(static fn (BetterReflectionAttribute $betterReflectionAttribute): ReflectionAttribute => new ReflectionAttribute($betterReflectionAttribute), $attributes);
    }

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
