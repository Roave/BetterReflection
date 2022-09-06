<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use Closure;
use OutOfBoundsException;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionExtension as CoreReflectionExtension;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionType as CoreReflectionType;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Exception\MethodPrototypeNotFound;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use Roave\BetterReflection\Util\FileHelper;
use Throwable;
use TypeError;
use ValueError;

use function array_map;
use function sprintf;

final class ReflectionMethod extends CoreReflectionMethod
{
    public function __construct(private BetterReflectionMethod $betterReflectionMethod)
    {
        unset($this->name);
        unset($this->class);
    }

    public function __toString(): string
    {
        return $this->betterReflectionMethod->__toString();
    }

    public function inNamespace(): bool
    {
        return $this->betterReflectionMethod->inNamespace();
    }

    public function isClosure(): bool
    {
        return $this->betterReflectionMethod->isClosure();
    }

    public function isDeprecated(): bool
    {
        return $this->betterReflectionMethod->isDeprecated();
    }

    public function isInternal(): bool
    {
        return $this->betterReflectionMethod->isInternal();
    }

    public function isUserDefined(): bool
    {
        return $this->betterReflectionMethod->isUserDefined();
    }

    public function getClosureThis(): object|null
    {
        throw new NotImplemented('Not implemented');
    }

    public function getClosureScopeClass(): CoreReflectionClass|null
    {
        throw new NotImplemented('Not implemented');
    }

    public function getClosureCalledClass(): CoreReflectionClass|null
    {
        throw new NotImplemented('Not implemented');
    }

    public function getDocComment(): string|false
    {
        return $this->betterReflectionMethod->getDocComment() ?: false;
    }

    public function getStartLine(): int|false
    {
        return $this->betterReflectionMethod->getStartLine();
    }

    public function getEndLine(): int|false
    {
        return $this->betterReflectionMethod->getEndLine();
    }

    /** @psalm-suppress ImplementedReturnTypeMismatch */
    public function getExtension(): CoreReflectionExtension|null
    {
        throw new NotImplemented('Not implemented');
    }

    public function getExtensionName(): string
    {
        return $this->betterReflectionMethod->getExtensionName() ?? '';
    }

    public function getFileName(): string|false
    {
        $fileName = $this->betterReflectionMethod->getFileName();

        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : false;
    }

    public function getName(): string
    {
        return $this->betterReflectionMethod->getName();
    }

    public function getNamespaceName(): string
    {
        return $this->betterReflectionMethod->getNamespaceName();
    }

    public function getNumberOfParameters(): int
    {
        return $this->betterReflectionMethod->getNumberOfParameters();
    }

    public function getNumberOfRequiredParameters(): int
    {
        return $this->betterReflectionMethod->getNumberOfRequiredParameters();
    }

    /** @return list<ReflectionParameter> */
    public function getParameters(): array
    {
        return array_map(
            static fn (BetterReflectionParameter $parameter): ReflectionParameter => new ReflectionParameter($parameter),
            $this->betterReflectionMethod->getParameters(),
        );
    }

    public function hasReturnType(): bool
    {
        return $this->betterReflectionMethod->hasReturnType();
    }

    public function getReturnType(): CoreReflectionType|null
    {
        return ReflectionType::fromTypeOrNull($this->betterReflectionMethod->getReturnType());
    }

    public function getShortName(): string
    {
        return $this->betterReflectionMethod->getShortName();
    }

    /** @return array<string, scalar> */
    public function getStaticVariables(): array
    {
        throw new NotImplemented('Not implemented');
    }

    public function returnsReference(): bool
    {
        return $this->betterReflectionMethod->returnsReference();
    }

    public function isGenerator(): bool
    {
        return $this->betterReflectionMethod->isGenerator();
    }

    public function isVariadic(): bool
    {
        return $this->betterReflectionMethod->isVariadic();
    }

    public function isPublic(): bool
    {
        return $this->betterReflectionMethod->isPublic();
    }

    public function isPrivate(): bool
    {
        return $this->betterReflectionMethod->isPrivate();
    }

    public function isProtected(): bool
    {
        return $this->betterReflectionMethod->isProtected();
    }

    public function isAbstract(): bool
    {
        return $this->betterReflectionMethod->isAbstract();
    }

    public function isFinal(): bool
    {
        return $this->betterReflectionMethod->isFinal();
    }

    public function isStatic(): bool
    {
        return $this->betterReflectionMethod->isStatic();
    }

    public function isConstructor(): bool
    {
        return $this->betterReflectionMethod->isConstructor();
    }

    public function isDestructor(): bool
    {
        return $this->betterReflectionMethod->isDestructor();
    }

    /** @psalm-suppress MethodSignatureMismatch */
    public function getClosure(object|null $object = null): Closure
    {
        try {
            return $this->betterReflectionMethod->getClosure($object);
        } catch (NoObjectProvided $e) {
            throw new ValueError($e->getMessage(), previous: $e);
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), previous: $e);
        }
    }

    public function getModifiers(): int
    {
        return $this->betterReflectionMethod->getModifiers();
    }

    public function invoke(object|null $object = null, mixed ...$args): mixed
    {
        try {
            return $this->betterReflectionMethod->invoke($object, ...$args);
        } catch (NoObjectProvided | TypeError) {
            return null;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), previous: $e);
        }
    }

    /** @param list<mixed> $args */
    public function invokeArgs(object|null $object = null, array $args = []): mixed
    {
        try {
            return $this->betterReflectionMethod->invokeArgs($object, $args);
        } catch (NoObjectProvided | TypeError) {
            return null;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), previous: $e);
        }
    }

    public function getDeclaringClass(): ReflectionClass
    {
        return new ReflectionClass($this->betterReflectionMethod->getImplementingClass());
    }

    public function getPrototype(): ReflectionMethod
    {
        return new self($this->betterReflectionMethod->getPrototype());
    }

    public function hasPrototype(): bool
    {
        try {
            $this->betterReflectionMethod->getPrototype();

            return true;
        } catch (MethodPrototypeNotFound) {
            return false;
        }
    }

    /**
     * @codeCoverageIgnore
     * @infection-ignore-all
     */
    public function setAccessible(bool $accessible): void
    {
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
            $attributes = $this->betterReflectionMethod->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionMethod->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionMethod->getAttributes();
        }

        return array_map(static fn (BetterReflectionAttribute $betterReflectionAttribute): ReflectionAttribute => new ReflectionAttribute($betterReflectionAttribute), $attributes);
    }

    public function hasTentativeReturnType(): bool
    {
        return $this->betterReflectionMethod->hasTentativeReturnType();
    }

    public function getTentativeReturnType(): CoreReflectionType|null
    {
        return ReflectionType::fromTypeOrNull($this->betterReflectionMethod->getTentativeReturnType());
    }

    /** @return mixed[] */
    public function getClosureUsedVariables(): array
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    public function __get(string $name): mixed
    {
        if ($name === 'name') {
            return $this->betterReflectionMethod->getName();
        }

        if ($name === 'class') {
            return $this->betterReflectionMethod->getImplementingClass()->getName();
        }

        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }
}
