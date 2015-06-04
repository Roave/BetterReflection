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

    private $flags;

    /**
     * @var ReflectionClass
     */
    private $declaringClass;

    /**
     * @param MethodNode $node
     * @param ReflectionClass $declaringClass
     * @return ReflectionMethod
     */
    public static function createFromNode(MethodNode $node, ReflectionClass $declaringClass)
    {
        $method = new self();
        $method->name = $node->name;
        $method->declaringClass = $declaringClass;

        $method->flags |= $node->isAbstract() ? self::IS_ABSTRACT : 0;
        $method->flags |= $node->isFinal() ? self::IS_FINAL : 0;
        $method->flags |= $node->isPrivate() ? self::IS_PRIVATE : 0;
        $method->flags |= $node->isProtected() ? self::IS_PROTECTED : 0;
        $method->flags |= $node->isPublic() ? self::IS_PUBLIC : 0;
        $method->flags |= $node->isStatic() ? self::IS_STATIC : 0;

        return $method;
    }

    private function flagsHas($flag)
    {
        return (bool)($this->flags & $flag);
    }

    public function isAbstract()
    {
        return $this->flagsHas(self::IS_ABSTRACT);
    }

    public function isFinal()
    {
        return $this->flagsHas(self::IS_FINAL);
    }

    public function isPrivate()
    {
        return $this->flagsHas(self::IS_PRIVATE);
    }

    public function isProtected()
    {
        return $this->flagsHas(self::IS_PROTECTED);
    }

    public function isPublic()
    {
        return $this->flagsHas(self::IS_PUBLIC);
    }

    public function isStatic()
    {
        return $this->flagsHas(self::IS_STATIC);
    }

    public function isConstructor()
    {
        return $this->name == '__construct';
    }

    public function isDestructor()
    {
        return $this->name == '__destruct';
    }
}
