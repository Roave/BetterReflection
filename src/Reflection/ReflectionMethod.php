<?php

namespace BetterReflection\Reflection;

use PhpParser\Node\Stmt\ClassMethod as MethodNode;

class ReflectionMethod extends ReflectionFunctionAbstract
{
    const IS_ABSTRACT = (1 << 0);
    const IS_FINAL = (1 << 1);
    const IS_PRIVATE = (1 << 2);
    const IS_PROTECTED = (1 << 3);
    const IS_PUBLIC = (1 << 4);
    const IS_STATIC = (1 << 5);

    /**
     * @var int
     */
    private $flags = 0;

    /**
     * @var ReflectionClass
     */
    private $declaringClass;

    /**
     * @param MethodNode $node
     * @param ReflectionClass $declaringClass
     * @return ReflectionMethod
     */
    public static function createFromNode(
        MethodNode $node,
        ReflectionClass $declaringClass
    ) {
        $method = new self($node);
        $method->declaringClass = $declaringClass;

        // Compat with core reflection means we should NOT pass namespace info
        // for ReflectionMethod
        $method->populateFunctionAbstract($node, $declaringClass->getLocatedSource(), null);

        $method->flags |= $node->isAbstract() ? self::IS_ABSTRACT : 0;
        $method->flags |= $node->isFinal() ? self::IS_FINAL : 0;
        $method->flags |= $node->isPrivate() ? self::IS_PRIVATE : 0;
        $method->flags |= $node->isProtected() ? self::IS_PROTECTED : 0;
        $method->flags |= $node->isPublic() ? self::IS_PUBLIC : 0;
        $method->flags |= $node->isStatic() ? self::IS_STATIC : 0;

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
        // @todo complete this implementation
        /* @see https://github.com/Roave/BetterReflection/issues/57 */
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
     * Check to see if a flag is set on this method.
     *
     * @param int $flag
     * @return bool
     */
    private function flagsHas($flag)
    {
        return (bool)($this->flags & $flag);
    }

    /**
     * Is the method abstract.
     *
     * @return bool
     */
    public function isAbstract()
    {
        return $this->flagsHas(self::IS_ABSTRACT);
    }

    /**
     * Is the method final.
     *
     * @return bool
     */
    public function isFinal()
    {
        return $this->flagsHas(self::IS_FINAL);
    }

    /**
     * Is the method private visibility.
     *
     * @return bool
     */
    public function isPrivate()
    {
        return $this->flagsHas(self::IS_PRIVATE);
    }

    /**
     * Is the method protected visibility.
     *
     * @return bool
     */
    public function isProtected()
    {
        return $this->flagsHas(self::IS_PROTECTED);
    }

    /**
     * Is the method public visibility.
     *
     * @return bool
     */
    public function isPublic()
    {
        return $this->flagsHas(self::IS_PUBLIC);
    }

    /**
     * Is the method static.
     *
     * @return bool
     */
    public function isStatic()
    {
        return $this->flagsHas(self::IS_STATIC);
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
