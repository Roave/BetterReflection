<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Closure;
use PhpParser\Node\Stmt\ClassMethod as MethodNode;
use PhpParser\Node\Stmt\Namespace_;
use ReflectionMethod as CoreReflectionMethod;
use Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflector\Reflector;

class ReflectionMethod extends ReflectionFunctionAbstract
{
    /**
     * @var ReflectionClass
     */
    private $declaringClass;

    /**
     * @var ReflectionClass
     */
    private $implementingClass;

    /**
     * @var MethodNode
     */
    private $methodNode;

    /**
     * @internal
     * @param Reflector       $reflector
     * @param MethodNode      $node Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     * @param Namespace_|null $namespace
     * @param ReflectionClass $declaringClass
     * @param ReflectionClass $implementingClass
     *
     * @throws \Roave\BetterReflection\Reflection\Exception\InvalidAbstractFunctionNodeType
     */
    public static function createFromNode(
        Reflector $reflector,
        MethodNode $node,
        ?Namespace_ $namespace,
        ReflectionClass $declaringClass,
        ReflectionClass $implementingClass
    ) : self {
        $method                    = new self();
        $method->declaringClass    = $declaringClass;
        $method->implementingClass = $implementingClass;
        $method->methodNode        = $node;

        $method->populateFunctionAbstract($reflector, $node, $declaringClass->getLocatedSource(), $namespace);

        return $method;
    }

    /**
     * Create a reflection of a method by it's name using a named class
     *
     * @param string $className
     * @param string $methodName
     *
     * @throws \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound
     * @throws \OutOfBoundsException
     */
    public static function createFromName(string $className, string $methodName) : self
    {
        return ReflectionClass::createFromName($className)->getMethod($methodName);
    }

    /**
     * Create a reflection of a method by it's name using an instance
     *
     * @param object $instance
     * @param string $methodName
     *
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     * @throws \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound
     * @throws \OutOfBoundsException
     */
    public static function createFromInstance($instance, string $methodName) : self
    {
        return ReflectionClass::createFromInstance($instance)->getMethod($methodName);
    }

    /**
     * Find the prototype for this method, if it exists. If it does not exist
     * it will throw a MethodPrototypeNotFound exception.
     *
     * @return ReflectionMethod
     * @throws Exception\MethodPrototypeNotFound
     */
    public function getPrototype() : self
    {
        $currentClass = $this->getDeclaringClass();

        while ($currentClass) {
            foreach ($currentClass->getImmediateInterfaces() as $interface) {
                if ($interface->hasMethod($this->getName())) {
                    return $interface->getMethod($this->getName());
                }
            }

            $currentClass = $currentClass->getParentClass();

            if (null === $currentClass || ! $currentClass->hasMethod($this->getName())) {
                break;
            }

            $prototype = $currentClass->getMethod($this->getName())->findPrototype();

            if (null !== $prototype) {
                return $prototype;
            }
        }

        throw new Exception\MethodPrototypeNotFound(\sprintf(
            'Method %s::%s does not have a prototype',
            $this->getDeclaringClass()->getName(),
            $this->getName()
        ));
    }

    private function findPrototype() : ?self
    {
        if ($this->isAbstract()) {
            return $this;
        }

        if ($this->isPrivate()) {
            return null;
        }

        try {
            return $this->getPrototype();
        } catch (Exception\MethodPrototypeNotFound $e) {
            return $this;
        }
    }

    /**
     * Get the core-reflection-compatible modifier values.
     *
     * @return int
     */
    public function getModifiers() : int
    {
        $val  = 0;
        $val += $this->isStatic() ? CoreReflectionMethod::IS_STATIC : 0;
        $val += $this->isPublic() ? CoreReflectionMethod::IS_PUBLIC : 0;
        $val += $this->isProtected() ? CoreReflectionMethod::IS_PROTECTED : 0;
        $val += $this->isPrivate() ? CoreReflectionMethod::IS_PRIVATE : 0;
        $val += $this->isAbstract() ? CoreReflectionMethod::IS_ABSTRACT : 0;
        $val += $this->isFinal() ? CoreReflectionMethod::IS_FINAL : 0;
        return $val;
    }

    /**
     * Return string representation of this parameter
     *
     * @return string
     */
    public function __toString() : string
    {
        $paramFormat = $this->getNumberOfParameters() > 0 ? "\n\n  - Parameters [%d] {%s\n  }" : '';

        try {
            $prototype = $this->getPrototype();
        } catch (Exception\MethodPrototypeNotFound $e) {
            $prototype = null;
        }

        $overwrittenMethod = $this->getOverwrittenMethod();

        return \sprintf(
            "Method [ <%s%s%s%s%s>%s%s%s %s method %s ] {%s{$paramFormat}\n}",
            $this->isUserDefined() ? 'user' : \sprintf('internal:%s', $this->getExtensionName()),
            $this->isConstructor() ? ', ctor' : '',
            $this->isDestructor() ? ', dtor' : '',
            null !== $overwrittenMethod ? \sprintf(', overwrites %s', $overwrittenMethod->getDeclaringClass()->getName()) : '',
            null !== $prototype ? \sprintf(', prototype %s', $prototype->getDeclaringClass()->getName()) : '',
            $this->isFinal() ? ' final' : '',
            $this->isStatic() ? ' static' : '',
            $this->isAbstract() ? ' abstract' : '',
            $this->getVisibilityAsString(),
            $this->getName(),
            $this->isUserDefined() ? \sprintf("\n  @@ %s %d - %d", $this->getFileName(), $this->getStartLine(), $this->getEndLine()) : '',
            \count($this->getParameters()),
            \array_reduce($this->getParameters(), function (string $str, ReflectionParameter $param) : string {
                return $str . "\n    " . (string) $param;
            }, '')
        );
    }

    private function getOverwrittenMethod() : ?self
    {
        $parentClass = $this->getDeclaringClass()->getParentClass();

        if ( ! $parentClass) {
            return null;
        }

        if ( ! $parentClass->hasMethod($this->getName())) {
            return null;
        }

        return $parentClass->getMethod($this->getName());
    }

    /**
     * Get the visibility of this method as a string (private/protected/public)
     *
     * @return string
     */
    private function getVisibilityAsString() : string
    {
        if ($this->isPrivate()) {
            return 'private';
        }

        if ($this->isProtected()) {
            return 'protected';
        }

        return 'public';
    }

    public function inNamespace() : bool
    {
        return false;
    }

    /**
     * Is the method abstract.
     *
     * @return bool
     */
    public function isAbstract() : bool
    {
        return $this->methodNode->isAbstract();
    }

    /**
     * Is the method final.
     *
     * @return bool
     */
    public function isFinal() : bool
    {
        return $this->methodNode->isFinal();
    }

    /**
     * Is the method private visibility.
     *
     * @return bool
     */
    public function isPrivate() : bool
    {
        return $this->methodNode->isPrivate();
    }

    /**
     * Is the method protected visibility.
     *
     * @return bool
     */
    public function isProtected() : bool
    {
        return $this->methodNode->isProtected();
    }

    /**
     * Is the method public visibility.
     *
     * @return bool
     */
    public function isPublic() : bool
    {
        return $this->methodNode->isPublic();
    }

    /**
     * Is the method static.
     *
     * @return bool
     */
    public function isStatic() : bool
    {
        return $this->methodNode->isStatic();
    }

    /**
     * Is the method a constructor.
     *
     * @return bool
     */
    public function isConstructor() : bool
    {
        if (\strtolower($this->getName()) === '__construct') {
            return true;
        }

        $declaringClass = $this->getDeclaringClass();
        if ($declaringClass->inNamespace()) {
            return false;
        }

        return \strtolower($this->getName()) === \strtolower($declaringClass->getShortName());
    }

    /**
     * Is the method a destructor.
     *
     * @return bool
     */
    public function isDestructor() : bool
    {
        return \strtolower($this->getName()) === '__destruct';
    }

    /**
     * Get the class that declares this method.
     *
     * @return ReflectionClass
     */
    public function getDeclaringClass() : ReflectionClass
    {
        return $this->declaringClass;
    }

    /**
     * @return ReflectionClass
     */
    public function getImplementingClass() : ReflectionClass
    {
        return $this->implementingClass;
    }

    public function getExtensionName() : ?string
    {
        return $this->getDeclaringClass()->getExtensionName();
    }

    public function isInternal() : bool
    {
        return $this->declaringClass->getLocatedSource()->isInternal();
    }

    /**
     * @param object|null $object
     *
     * @return \Closure
     *
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws NotAnObject
     * @throws ObjectNotInstanceOfClass
     */
    public function getClosure($object = null) : Closure
    {
        $declaringClassName = $this->getDeclaringClass()->getName();

        if ($this->isStatic()) {
            $this->assertClassExist($declaringClassName);

            return function (...$args) {
                return $this->callStaticMethod($args);
            };
        }

        $instance = $this->assertObject($object);

        return function (...$args) use ($instance) {
            return $this->callObjectMethod($instance, $args);
        };
    }

    /**
     * @param object|null $object
     * @param mixed ...$args
     *
     * @return mixed
     *
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws NotAnObject
     * @throws ObjectNotInstanceOfClass
     */
    public function invoke($object = null, ...$args)
    {
        return $this->invokeArgs($object, $args);
    }

    /**
     * @param object|null $object
     * @param mixed[] $args
     *
     * @return mixed
     *
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws NotAnObject
     * @throws ObjectNotInstanceOfClass
     */
    public function invokeArgs($object = null, array $args = [])
    {
        $declaringClassName = $this->getDeclaringClass()->getName();

        if ($this->isStatic()) {
            $this->assertClassExist($declaringClassName);

            return $this->callStaticMethod($args);
        }

        return $this->callObjectMethod($this->assertObject($object), $args);
    }

    /**
     * @param mixed[] $args
     *
     * @return mixed
     */
    private function callStaticMethod(array $args)
    {
        $declaringClassName = $this->getDeclaringClass()->getName();

        return Closure::bind(function (string $declaringClassName, string $methodName, array $methodArgs) {
            return $declaringClassName::{$methodName}(...$methodArgs);
        }, null, $declaringClassName)->__invoke($declaringClassName, $this->getName(), $args);
    }

    /**
     * @param object $object
     * @param mixed[] $args
     *
     * @return mixed
     */
    private function callObjectMethod($object, array $args)
    {
        return Closure::bind(function ($object, string $methodName, array $methodArgs) {
            return $object->{$methodName}(...$methodArgs);
        }, $object, $this->getDeclaringClass()->getName())->__invoke($object, $this->getName(), $args);
    }

    /**
     * @throws ClassDoesNotExist
     */
    private function assertClassExist(string $className) : void
    {
        if ( ! \class_exists($className, false)) {
            throw new ClassDoesNotExist(\sprintf('Method of class %s cannot be used as the class is not loaded', $className));
        }
    }

    /**
     * @param mixed $object
     *
     * @throws NoObjectProvided
     * @throws NotAnObject
     * @throws ObjectNotInstanceOfClass
     *
     * @return object
     */
    private function assertObject($object)
    {
        if (null === $object) {
            throw NoObjectProvided::create();
        }

        if ( ! \is_object($object)) {
            throw NotAnObject::fromNonObject($object);
        }

        $declaringClassName = $this->getDeclaringClass()->getName();

        if (\get_class($object) !== $declaringClassName) {
            throw ObjectNotInstanceOfClass::fromClassName($declaringClassName);
        }

        return $object;
    }
}
