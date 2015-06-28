<?php

namespace Asgrim;

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
    private $flags;

    /**
     * @var ReflectionClass
     */
    private $declaringClass;

    protected function __construct()
    {
        parent::__construct();

        $this->flags = 0;
    }

    /**
     * @param MethodNode $node
     * @param ReflectionClass $declaringClass
     * @return ReflectionMethod
     */
    public static function createFromNode(MethodNode $node, ReflectionClass $declaringClass)
    {
        $method = new self($node);
        $method->populateFunctionAbstract($node, $declaringClass->getFileName());

        $method->declaringClass = $declaringClass;

        $method->flags |= $node->isAbstract() ? self::IS_ABSTRACT : 0;
        $method->flags |= $node->isFinal() ? self::IS_FINAL : 0;
        $method->flags |= $node->isPrivate() ? self::IS_PRIVATE : 0;
        $method->flags |= $node->isProtected() ? self::IS_PROTECTED : 0;
        $method->flags |= $node->isPublic() ? self::IS_PUBLIC : 0;
        $method->flags |= $node->isStatic() ? self::IS_STATIC : 0;

        return $method;
    }

    /**
     * Check to see if a flag is set on this method
     *
     * @param int $flag
     * @return bool
     */
    private function flagsHas($flag)
    {
        return (bool)($this->flags & $flag);
    }

    /**
     * Is the method abstract
     *
     * @return bool
     */
    public function isAbstract()
    {
        return $this->flagsHas(self::IS_ABSTRACT);
    }

    /**
     * Is the method final
     *
     * @return bool
     */
    public function isFinal()
    {
        return $this->flagsHas(self::IS_FINAL);
    }

    /**
     * Is the method private visibility
     *
     * @return bool
     */
    public function isPrivate()
    {
        return $this->flagsHas(self::IS_PRIVATE);
    }

    /**
     * Is the method protected visibility
     *
     * @return bool
     */
    public function isProtected()
    {
        return $this->flagsHas(self::IS_PROTECTED);
    }

    /**
     * Is the method public visibility
     *
     * @return bool
     */
    public function isPublic()
    {
        return $this->flagsHas(self::IS_PUBLIC);
    }

    /**
     * Is the method static
     *
     * @return bool
     */
    public function isStatic()
    {
        return $this->flagsHas(self::IS_STATIC);
    }

    /**
     * Is the method a constructor
     *
     * @return bool
     */
    public function isConstructor()
    {
        return $this->getName() == '__construct';
    }

    /**
     * Is the method a destructor
     *
     * @return bool
     */
    public function isDestructor()
    {
        return $this->getName() == '__destruct';
    }

    /**
     * Get the class that declares this method
     *
     * @return ReflectionClass
     */
    public function getDeclaringClass()
    {
        return $this->declaringClass;
    }
}
