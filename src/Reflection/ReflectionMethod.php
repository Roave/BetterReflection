<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Closure;
use OutOfBoundsException;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod as MethodNode;
use ReflectionException;
use ReflectionMethod as CoreReflectionMethod;
use Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflection\StringCast\ReflectionMethodStringCast;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\Util\ClassExistenceChecker;

use function array_map;
use function assert;
use function sprintf;
use function strtolower;

class ReflectionMethod
{
    use ReflectionFunctionAbstract;

    private int $modifiers;

    /** @param non-empty-string|null $aliasName */
    private function __construct(
        private Reflector $reflector,
        MethodNode|Node\Stmt\Function_|Node\Expr\Closure|Node\Expr\ArrowFunction $node,
        private LocatedSource $locatedSource,
        private string|null $namespace,
        private ReflectionClass $declaringClass,
        private ReflectionClass $implementingClass,
        private ReflectionClass $currentClass,
        private string|null $aliasName,
    ) {
        assert($node instanceof MethodNode);

        $name = $node->name->name;
        assert($name !== '');

        $this->name      = $name;
        $this->modifiers = $this->computeModifiers($node);

        $this->fillFromNode($node);
    }

    /**
     * @internal
     *
     * @param non-empty-string|null $aliasName
     */
    public static function createFromNode(
        Reflector $reflector,
        MethodNode $node,
        LocatedSource $locatedSource,
        string|null $namespace,
        ReflectionClass $declaringClass,
        ReflectionClass $implementingClass,
        ReflectionClass $currentClass,
        string|null $aliasName = null,
    ): self {
        return new self(
            $reflector,
            $node,
            $locatedSource,
            $namespace,
            $declaringClass,
            $implementingClass,
            $currentClass,
            $aliasName,
        );
    }

    /**
     * Create a reflection of a method by it's name using a named class
     *
     * @throws IdentifierNotFound
     * @throws OutOfBoundsException
     */
    public static function createFromName(string $className, string $methodName): self
    {
        $method = ReflectionClass::createFromName($className)->getMethod($methodName);

        if ($method === null) {
            throw new OutOfBoundsException(sprintf('Could not find method: %s', $methodName));
        }

        return $method;
    }

    /**
     * Create a reflection of a method by it's name using an instance
     *
     * @throws ReflectionException
     * @throws IdentifierNotFound
     * @throws OutOfBoundsException
     */
    public static function createFromInstance(object $instance, string $methodName): self
    {
        $method = ReflectionClass::createFromInstance($instance)->getMethod($methodName);

        if ($method === null) {
            throw new OutOfBoundsException(sprintf('Could not find method: %s', $methodName));
        }

        return $method;
    }

    /**
     * @internal
     *
     * @param non-empty-string|null $aliasName
     */
    public function withImplementingClass(ReflectionClass $implementingClass, string|null $aliasName, int $modifiers): self
    {
        $clone                    = $this->clone();
        $clone->aliasName         = $aliasName;
        $clone->modifiers         = $modifiers;
        $clone->implementingClass = $implementingClass;
        $clone->currentClass      = $implementingClass;

        return $clone;
    }

    /** @internal */
    public function withCurrentClass(ReflectionClass $currentClass): self
    {
        $clone               = $this->clone();
        $clone->currentClass = $currentClass;

        return $clone;
    }

    private function clone(): self
    {
        $clone = clone $this;

        if ($clone->returnType !== null) {
            $clone->returnType = $clone->returnType->withOwner($clone);
        }

        $clone->parameters = array_map(static fn (ReflectionParameter $parameter): ReflectionParameter => $parameter->withFunction($clone), $this->parameters);

        $clone->attributes = array_map(static fn (ReflectionAttribute $attribute): ReflectionAttribute => $attribute->withOwner($clone), $this->attributes);

        return $clone;
    }

    /** @return non-empty-string */
    public function getShortName(): string
    {
        if ($this->aliasName !== null) {
            return $this->aliasName;
        }

        return $this->name;
    }

    /** @return non-empty-string|null */
    public function getAliasName(): string|null
    {
        return $this->aliasName;
    }

    /**
     * Find the prototype for this method, if it exists. If it does not exist
     * it will throw a MethodPrototypeNotFound exception.
     *
     * @throws Exception\MethodPrototypeNotFound
     */
    public function getPrototype(): self
    {
        $currentClass = $this->getImplementingClass();

        while ($currentClass) {
            foreach ($currentClass->getImmediateInterfaces() as $interface) {
                $interfaceMethod = $interface->getMethod($this->getName());

                if ($interfaceMethod !== null) {
                    return $interfaceMethod;
                }
            }

            $currentClass = $currentClass->getParentClass();

            if ($currentClass === null || ! $currentClass->hasMethod($this->getName())) {
                // @infection-ignore-all Break_: There's no difference between break and continue - break is just optimization
                break;
            }

            $prototype = $currentClass->getMethod($this->getName())?->findPrototype();

            if ($prototype === null) {
                // @infection-ignore-all Break_: There's no difference between break and continue - break is just optimization
                break;
            }

            if (! $this->isConstructor() || $prototype->isAbstract()) {
                return $prototype;
            }
        }

        throw new Exception\MethodPrototypeNotFound(sprintf(
            'Method %s::%s does not have a prototype',
            $this->getDeclaringClass()->getName(),
            $this->getName(),
        ));
    }

    private function findPrototype(): self|null
    {
        if ($this->isAbstract()) {
            return $this;
        }

        if ($this->isPrivate()) {
            return null;
        }

        try {
            return $this->getPrototype();
        } catch (Exception\MethodPrototypeNotFound) {
            return $this;
        }
    }

    /**
     * Get the core-reflection-compatible modifier values.
     */
    public function getModifiers(): int
    {
        return $this->modifiers;
    }

    private function computeModifiers(MethodNode $node): int
    {
        $modifiers  = $node->isStatic() ? CoreReflectionMethod::IS_STATIC : 0;
        $modifiers += $node->isPublic() ? CoreReflectionMethod::IS_PUBLIC : 0;
        $modifiers += $node->isProtected() ? CoreReflectionMethod::IS_PROTECTED : 0;
        $modifiers += $node->isPrivate() ? CoreReflectionMethod::IS_PRIVATE : 0;
        $modifiers += $node->isAbstract() ? CoreReflectionMethod::IS_ABSTRACT : 0;
        $modifiers += $node->isFinal() ? CoreReflectionMethod::IS_FINAL : 0;

        return $modifiers;
    }

    public function __toString(): string
    {
        return ReflectionMethodStringCast::toString($this);
    }

    public function inNamespace(): bool
    {
        return false;
    }

    public function getNamespaceName(): string|null
    {
        return null;
    }

    public function isClosure(): bool
    {
        return false;
    }

    /**
     * Is the method abstract.
     */
    public function isAbstract(): bool
    {
        return ($this->modifiers & CoreReflectionMethod::IS_ABSTRACT) === CoreReflectionMethod::IS_ABSTRACT
            || $this->declaringClass->isInterface();
    }

    /**
     * Is the method final.
     */
    public function isFinal(): bool
    {
        return ($this->modifiers & CoreReflectionMethod::IS_FINAL) === CoreReflectionMethod::IS_FINAL;
    }

    /**
     * Is the method private visibility.
     */
    public function isPrivate(): bool
    {
        return ($this->modifiers & CoreReflectionMethod::IS_PRIVATE) === CoreReflectionMethod::IS_PRIVATE;
    }

    /**
     * Is the method protected visibility.
     */
    public function isProtected(): bool
    {
        return ($this->modifiers & CoreReflectionMethod::IS_PROTECTED) === CoreReflectionMethod::IS_PROTECTED;
    }

    /**
     * Is the method public visibility.
     */
    public function isPublic(): bool
    {
        return ($this->modifiers & CoreReflectionMethod::IS_PUBLIC) === CoreReflectionMethod::IS_PUBLIC;
    }

    /**
     * Is the method static.
     */
    public function isStatic(): bool
    {
        return ($this->modifiers & CoreReflectionMethod::IS_STATIC) === CoreReflectionMethod::IS_STATIC;
    }

    /**
     * Is the method a constructor.
     */
    public function isConstructor(): bool
    {
        if (strtolower($this->getName()) === '__construct') {
            return true;
        }

        $declaringClass = $this->getDeclaringClass();
        if ($declaringClass->inNamespace()) {
            return false;
        }

        return strtolower($this->getName()) === strtolower($declaringClass->getShortName());
    }

    /**
     * Is the method a destructor.
     */
    public function isDestructor(): bool
    {
        return strtolower($this->getName()) === '__destruct';
    }

    /**
     * Get the class that declares this method.
     */
    public function getDeclaringClass(): ReflectionClass
    {
        return $this->declaringClass;
    }

    /**
     * Get the class that implemented the method based on trait use.
     */
    public function getImplementingClass(): ReflectionClass
    {
        return $this->implementingClass;
    }

    /**
     * Get the current reflected class.
     */
    public function getCurrentClass(): ReflectionClass
    {
        return $this->currentClass;
    }

    /**
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws ObjectNotInstanceOfClass
     */
    public function getClosure(object|null $object = null): Closure
    {
        $declaringClassName = $this->getDeclaringClass()->getName();

        if ($this->isStatic()) {
            $this->assertClassExist($declaringClassName);

            return fn (mixed ...$args): mixed => $this->callStaticMethod($args);
        }

        $instance = $this->assertObject($object);

        return fn (mixed ...$args): mixed => $this->callObjectMethod($instance, $args);
    }

    /**
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws ObjectNotInstanceOfClass
     */
    public function invoke(object|null $object = null, mixed ...$args): mixed
    {
        return $this->invokeArgs($object, $args);
    }

    /**
     * @param array<mixed> $args
     *
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws ObjectNotInstanceOfClass
     */
    public function invokeArgs(object|null $object = null, array $args = []): mixed
    {
        $implementingClassName = $this->getImplementingClass()->getName();

        if ($this->isStatic()) {
            $this->assertClassExist($implementingClassName);

            return $this->callStaticMethod($args);
        }

        return $this->callObjectMethod($this->assertObject($object), $args);
    }

    /** @param array<mixed> $args */
    private function callStaticMethod(array $args): mixed
    {
        $implementingClassName = $this->getImplementingClass()->getName();

        /** @psalm-suppress InvalidStringClass */
        $closure = Closure::bind(fn (string $implementingClassName, string $_methodName, array $methodArgs): mixed => $implementingClassName::{$_methodName}(...$methodArgs), null, $implementingClassName);

        assert($closure instanceof Closure);

        return $closure->__invoke($implementingClassName, $this->getName(), $args);
    }

    /** @param array<mixed> $args */
    private function callObjectMethod(object $object, array $args): mixed
    {
        /** @psalm-suppress MixedMethodCall */
        $closure = Closure::bind(fn (object $object, string $methodName, array $methodArgs): mixed => $object->{$methodName}(...$methodArgs), $object, $this->getImplementingClass()->getName());

        assert($closure instanceof Closure);

        return $closure->__invoke($object, $this->getName(), $args);
    }

    /** @throws ClassDoesNotExist */
    private function assertClassExist(string $className): void
    {
        if (! ClassExistenceChecker::classExists($className) && ! ClassExistenceChecker::traitExists($className)) {
            throw new ClassDoesNotExist(sprintf('Method of class %s cannot be used as the class is not loaded', $className));
        }
    }

    /**
     * @throws NoObjectProvided
     * @throws ObjectNotInstanceOfClass
     */
    private function assertObject(object|null $object): object
    {
        if ($object === null) {
            throw NoObjectProvided::create();
        }

        $implementingClassName = $this->getImplementingClass()->getName();

        if ($object::class !== $implementingClassName) {
            throw ObjectNotInstanceOfClass::fromClassName($implementingClassName);
        }

        return $object;
    }
}
