<?php

namespace BetterReflection\Reflection;

use BetterReflection\Reflector\Reflector;
use PhpParser\Node\Stmt\ClassMethod as MethodNode;

class ReflectionMethod extends ReflectionFunctionAbstract
{
    /**
     * @var ReflectionClass
     */
    private $declaringClass;

    /**
     * @param Reflector $reflector
     * @param MethodNode $node
     * @param ReflectionClass $declaringClass
     * @return ReflectionMethod
     */
    public static function createFromNode(
        Reflector $reflector,
        MethodNode $node,
        ReflectionClass $declaringClass
    ) {
        $method = new self();
        $method->declaringClass = $declaringClass;

        // Compat with core reflection means we should NOT pass namespace info
        // for ReflectionMethod
        $method->populateFunctionAbstract($reflector, $node, $declaringClass->getLocatedSource(), null);

        return $method;
    }

    /**
     * Find the prototype for this method, if it exists. If it does not exist
     * it will throw a MethodPrototypeNotFound exception.
     *
     * @return ReflectionMethod
     * @throws Exception\MethodPrototypeNotFound
     */
    public function getPrototype()
    {
        $i = $this->getDeclaringClass();

        while ($i) {
            foreach ($i->getImmediateInterfaces() as $interface) {
                if ($interface->hasMethod($this->getName())) {
                    return $interface->getMethod($this->getName());
                }
            }

            $i = $i->getParentClass();

            if (null === $i) {
                continue;
            }

            if ($i->hasMethod($this->getName()) && $i->getMethod($this->getName())->isAbstract()) {
                return $i->getMethod($this->getName());
            }
        }

        throw new Exception\MethodPrototypeNotFound(sprintf(
            'Method %s::%s does not have a prototype',
            $this->getDeclaringClass()->getName(),
            $this->getName()
        ));
    }

    /**
     * Get the core-reflection-compatible modifier values.
     *
     * @return int
     */
    public function getModifiers()
    {
        $val = 0;
        $val += $this->isStatic() ? \ReflectionMethod::IS_STATIC : 0;
        $val += $this->isPublic() ? \ReflectionMethod::IS_PUBLIC : 0;
        $val += $this->isProtected() ? \ReflectionMethod::IS_PROTECTED : 0;
        $val += $this->isPrivate() ? \ReflectionMethod::IS_PRIVATE : 0;
        $val += $this->isAbstract() ? \ReflectionMethod::IS_ABSTRACT : 0;
        $val += $this->isFinal() ? \ReflectionMethod::IS_FINAL : 0;
        return $val;
    }

    /**
     * Return string representation of this parameter
     *
     * @return string
     */
    public function __toString()
    {
        $paramFormat = ($this->getNumberOfParameters() > 0) ? "\n\n  - Parameters [%d] {%s\n  }" : '';

        return sprintf(
            "Method [ <user%s%s>%s%s%s %s method %s ] {\n  @@ %s %d - %d{$paramFormat}\n}",
            $this->isConstructor() ? ', ctor' : '',
            $this->isDestructor() ? ', dtor' : '',
            $this->isFinal() ? ' final' : '',
            $this->isStatic() ? ' static' : '',
            $this->isAbstract() ? ' abstract' : '',
            $this->getVisibilityAsString(),
            $this->getName(),
            $this->getFileName(),
            $this->getStartLine(),
            $this->getEndLine(),
            count($this->getParameters()),
            array_reduce($this->getParameters(), function ($str, ReflectionParameter $param) {
                return $str . "\n    " . $param;
            }, '')
        );
    }

    /**
     * Get the visibility of this method as a string (private/protected/public)
     *
     * @return string
     */
    private function getVisibilityAsString()
    {
        if ($this->isPrivate()) {
            return 'private';
        }

        if ($this->isProtected()) {
            return 'protected';
        }

        return 'public';
    }

    /**
     * Get the method node (ensuring it is a ClassMethod node)
     *
     * @throws \RuntimeException
     * @return MethodNode
     */
    private function getMethodNode()
    {
        if (!($this->getNode() instanceof MethodNode)) {
            throw new \RuntimeException('Expected a ClassMethod node');
        }
        return $this->getNode();
    }

    /**
     * Is the method abstract.
     *
     * @return bool
     */
    public function isAbstract()
    {
        return $this->getMethodNode()->isAbstract();
    }

    /**
     * Is the method final.
     *
     * @return bool
     */
    public function isFinal()
    {
        return $this->getMethodNode()->isFinal();
    }

    /**
     * Is the method private visibility.
     *
     * @return bool
     */
    public function isPrivate()
    {
        return $this->getMethodNode()->isPrivate();
    }

    /**
     * Is the method protected visibility.
     *
     * @return bool
     */
    public function isProtected()
    {
        return $this->getMethodNode()->isProtected();
    }

    /**
     * Is the method public visibility.
     *
     * @return bool
     */
    public function isPublic()
    {
        return $this->getMethodNode()->isPublic();
    }

    /**
     * Is the method static.
     *
     * @return bool
     */
    public function isStatic()
    {
        return $this->getMethodNode()->isStatic();
    }

    /**
     * Is the method a constructor.
     *
     * @return bool
     */
    public function isConstructor()
    {
        return $this->getName() === '__construct';
    }

    /**
     * Is the method a destructor.
     *
     * @return bool
     */
    public function isDestructor()
    {
        return $this->getName() === '__destruct';
    }

    /**
     * Get the class that declares this method.
     *
     * @return ReflectionClass
     */
    public function getDeclaringClass()
    {
        return $this->declaringClass;
    }
}
