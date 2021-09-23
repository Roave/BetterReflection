<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use Closure;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionExtension as CoreReflectionExtension;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionType as CoreReflectionType;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Util\FileHelper;
use Throwable;
use TypeError;
use ValueError;

use function array_map;
use function func_get_args;

final class ReflectionMethod extends CoreReflectionMethod
{
    private bool $accessible = false;

    public function __construct(private BetterReflectionMethod $betterReflectionMethod)
    {
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

    public function getClosureThis(): ?object
    {
        throw new NotImplemented('Not implemented');
    }

    public function getClosureScopeClass(): ?CoreReflectionClass
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

    /**
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function getExtension(): ?CoreReflectionExtension
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

    /**
     * @return list<ReflectionParameter>
     */
    public function getParameters(): array
    {
        $parameters = $this->betterReflectionMethod->getParameters();

        $wrappedParameters = [];
        foreach ($parameters as $key => $parameter) {
            $wrappedParameters[$key] = new ReflectionParameter($parameter);
        }

        return $wrappedParameters;
    }

    public function hasReturnType(): bool
    {
        return $this->betterReflectionMethod->hasReturnType();
    }

    public function getReturnType(): ?CoreReflectionType
    {
        return ReflectionType::fromTypeOrNull($this->betterReflectionMethod->getReturnType());
    }

    public function getShortName(): string
    {
        return $this->betterReflectionMethod->getShortName();
    }

    /**
     * @return array<string, scalar>
     */
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

    /**
     * @psalm-suppress MethodSignatureMismatch
     */
    public function getClosure(?object $object = null): Closure
    {
        try {
            return $this->betterReflectionMethod->getClosure($object);
        } catch (NoObjectProvided $e) {
            throw new ValueError($e->getMessage(), 0, $e);
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    public function getModifiers(): int
    {
        return $this->betterReflectionMethod->getModifiers();
    }

    public function invoke(?object $object = null, mixed ...$args): mixed
    {
        if (! $this->isAccessible()) {
            throw new CoreReflectionException('Method not accessible');
        }

        try {
            return $this->betterReflectionMethod->invoke(...func_get_args());
        } catch (NoObjectProvided | TypeError) {
            return null;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param list<mixed> $args
     */
    public function invokeArgs(?object $object = null, array $args = []): mixed
    {
        if (! $this->isAccessible()) {
            throw new CoreReflectionException('Method not accessible');
        }

        try {
            return $this->betterReflectionMethod->invokeArgs($object, $args);
        } catch (NoObjectProvided | TypeError) {
            return null;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
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

    public function setAccessible(bool $accessible): void
    {
        $this->accessible = true;
    }

    private function isAccessible(): bool
    {
        return $this->accessible || $this->isPublic();
    }

    /**
     * @param class-string|null $name
     *
     * @return list<ReflectionAttribute>
     */
    public function getAttributes(?string $name = null, int $flags = 0): array
    {
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

    public function getTentativeReturnType(): ?CoreReflectionType
    {
        return ReflectionType::fromTypeOrNull($this->betterReflectionMethod->getTentativeReturnType());
    }

    /**
     * @return mixed[]
     */
    public function getClosureUsedVariables(): array
    {
        throw new Exception\NotImplemented('Not implemented');
    }
}
