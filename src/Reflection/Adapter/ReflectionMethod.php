<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use Closure;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\IgnoreMethodForCodeCoverage;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionExtension as CoreReflectionExtension;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionType as CoreReflectionType;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Exception\CodeLocationMissing;
use Roave\BetterReflection\Reflection\Exception\MethodPrototypeNotFound;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use Roave\BetterReflection\Util\FileHelper;
use Throwable;
use ValueError;

use function array_map;
use function sprintf;

/** @psalm-suppress PropertyNotSetInConstructor */
#[IgnoreMethodForCodeCoverage(ReflectionMethod::class, 'setAccessible')]
final class ReflectionMethod extends CoreReflectionMethod
{
    public function __construct(private BetterReflectionMethod $betterReflectionMethod)
    {
        unset($this->name);
        unset($this->class);
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->betterReflectionMethod->__toString();
    }

    /** @psalm-mutation-free */
    public function inNamespace(): bool
    {
        return $this->betterReflectionMethod->inNamespace();
    }

    /** @psalm-mutation-free */
    public function isClosure(): bool
    {
        return $this->betterReflectionMethod->isClosure();
    }

    /** @psalm-mutation-free */
    public function isDeprecated(): bool
    {
        return $this->betterReflectionMethod->isDeprecated();
    }

    /** @psalm-mutation-free */
    public function isInternal(): bool
    {
        return $this->betterReflectionMethod->isInternal();
    }

    /** @psalm-mutation-free */
    public function isUserDefined(): bool
    {
        return $this->betterReflectionMethod->isUserDefined();
    }

    public function getClosureThis(): object|null
    {
        throw new NotImplemented('Not implemented');
    }

    /** @psalm-mutation-free */
    public function getClosureScopeClass(): CoreReflectionClass|null
    {
        throw new NotImplemented('Not implemented');
    }

    /** @psalm-mutation-free */
    public function getClosureCalledClass(): CoreReflectionClass|null
    {
        throw new NotImplemented('Not implemented');
    }

    /** @psalm-mutation-free */
    public function getDocComment(): string|false
    {
        return $this->betterReflectionMethod->getDocComment() ?? false;
    }

    /** @psalm-mutation-free */
    public function getStartLine(): int|false
    {
        try {
            return $this->betterReflectionMethod->getStartLine();
        } catch (CodeLocationMissing) {
            return false;
        }
    }

    /** @psalm-mutation-free */
    public function getEndLine(): int|false
    {
        try {
            return $this->betterReflectionMethod->getEndLine();
        } catch (CodeLocationMissing) {
            return false;
        }
    }

    /** @psalm-mutation-free */
    public function getExtension(): CoreReflectionExtension|null
    {
        throw new NotImplemented('Not implemented');
    }

    /**
     * @return non-empty-string|false
     *
     * @psalm-mutation-free
     */
    public function getExtensionName(): string|false
    {
        return $this->betterReflectionMethod->getExtensionName() ?? false;
    }

    /**
     * @return non-empty-string|false
     *
     * @psalm-mutation-free
     */
    public function getFileName(): string|false
    {
        $fileName = $this->betterReflectionMethod->getFileName();

        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : false;
    }

    /** @psalm-mutation-free */
    public function getName(): string
    {
        return $this->betterReflectionMethod->getName();
    }

    /** @psalm-mutation-free */
    public function getNamespaceName(): string
    {
        return $this->betterReflectionMethod->getNamespaceName() ?? '';
    }

    /** @psalm-mutation-free */
    public function getNumberOfParameters(): int
    {
        return $this->betterReflectionMethod->getNumberOfParameters();
    }

    /** @psalm-mutation-free */
    public function getNumberOfRequiredParameters(): int
    {
        return $this->betterReflectionMethod->getNumberOfRequiredParameters();
    }

    /**
     * @return list<ReflectionParameter>
     *
     * @psalm-mutation-free
     */
    public function getParameters(): array
    {
        return array_map(
            static fn (BetterReflectionParameter $parameter): ReflectionParameter => new ReflectionParameter($parameter),
            $this->betterReflectionMethod->getParameters(),
        );
    }

    /** @psalm-mutation-free */
    public function hasReturnType(): bool
    {
        return $this->betterReflectionMethod->hasReturnType();
    }

    /** @psalm-mutation-free */
    public function getReturnType(): CoreReflectionType|null
    {
        return ReflectionType::fromTypeOrNull($this->betterReflectionMethod->getReturnType());
    }

    /** @psalm-mutation-free */
    public function getShortName(): string
    {
        return $this->betterReflectionMethod->getShortName();
    }

    /** @return array<string, scalar> */
    public function getStaticVariables(): array
    {
        throw new NotImplemented('Not implemented');
    }

    /** @psalm-mutation-free */
    public function returnsReference(): bool
    {
        return $this->betterReflectionMethod->returnsReference();
    }

    /** @psalm-mutation-free */
    public function isGenerator(): bool
    {
        return $this->betterReflectionMethod->isGenerator();
    }

    /** @psalm-mutation-free */
    public function isVariadic(): bool
    {
        return $this->betterReflectionMethod->isVariadic();
    }

    /** @psalm-mutation-free */
    public function isPublic(): bool
    {
        return $this->betterReflectionMethod->isPublic();
    }

    /** @psalm-mutation-free */
    public function isPrivate(): bool
    {
        return $this->betterReflectionMethod->isPrivate();
    }

    /** @psalm-mutation-free */
    public function isProtected(): bool
    {
        return $this->betterReflectionMethod->isProtected();
    }

    /** @psalm-mutation-free */
    public function isAbstract(): bool
    {
        return $this->betterReflectionMethod->isAbstract();
    }

    /** @psalm-mutation-free */
    public function isFinal(): bool
    {
        return $this->betterReflectionMethod->isFinal();
    }

    /** @psalm-mutation-free */
    public function isStatic(): bool
    {
        return $this->betterReflectionMethod->isStatic();
    }

    /** @psalm-mutation-free */
    public function isConstructor(): bool
    {
        return $this->betterReflectionMethod->isConstructor();
    }

    /** @psalm-mutation-free */
    public function isDestructor(): bool
    {
        return $this->betterReflectionMethod->isDestructor();
    }

    /** @psalm-mutation-free */
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

    /** @psalm-mutation-free */
    public function getModifiers(): int
    {
        return $this->betterReflectionMethod->getModifiers();
    }

    public function invoke(object|null $object = null, mixed ...$args): mixed
    {
        try {
            return $this->betterReflectionMethod->invoke($object, ...$args);
        } catch (NoObjectProvided) {
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
        } catch (NoObjectProvided) {
            return null;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), previous: $e);
        }
    }

    /** @psalm-mutation-free */
    public function getDeclaringClass(): ReflectionClass
    {
        return new ReflectionClass($this->betterReflectionMethod->getImplementingClass());
    }

    /** @psalm-mutation-free */
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
     * @infection-ignore-all
     * @psalm-pure
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
