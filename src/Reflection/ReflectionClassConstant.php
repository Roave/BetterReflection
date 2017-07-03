<?php

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\Util\GetFirstDocComment;

class ReflectionClassConstant implements \Reflector
{
    /**
     * @var bool
     */
    private $valueWasCached = false;

    /**
     * @var int|float|array|string|bool|null const value
     */
    private $value;

    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var ReflectionClass Constant owner
     */
    private $owner;

    /**
     * @var ClassConst
     */
    private $node;

    private function __construct()
    {
    }

    /**
     * Create a reflection of a class's constant by Const Node
     *
     * @param Reflector $reflector
     * @param ClassConst $node
     * @param ReflectionClass $owner
     * @return ReflectionClassConstant
     */
    public static function createFromNode(
        Reflector $reflector,
        ClassConst $node,
        ReflectionClass $owner
    ) : self {
        $ref = new self();
        $ref->node = $node;
        $ref->owner = $owner;
        $ref->reflector = $reflector;
        return $ref;
    }

    /**
     * Get the name of the reflection (e.g. if this is a ReflectionClass this
     * will be the class name).
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->node->consts[0]->name;
    }

    /**
     * Returns constant value
     *
     * @return mixed
     */
    public function getValue()
    {
        if (false !== $this->valueWasCached) {
            return $this->value;
        }

        $this->value = (new CompileNodeToValue())->__invoke(
            $this->node->consts[0]->value,
            new CompilerContext($this->reflector, $this->getDeclaringClass())
        );
        $this->valueWasCached = true;
        return $this->value;
    }

    /**
     * Constant is public
     *
     * @return bool
     */
    public function isPublic() : bool
    {
        return $this->node->isPublic();
    }

    /**
     * Cosnstant is private
     *
     * @return bool
     */
    public function isPrivate() : bool
    {
        return $this->node->isPrivate();
    }

    /**
     * Constant is protected
     *
     * @return bool
     */
    public function isProtected() : bool
    {
        return $this->node->isProtected();
    }

    /**
     * Returns a bitfield of the access modifiers for this constant
     *
     * @return int
     */
    public function getModifiers() : int
    {
        $val = 0;
        $val += $this->isPublic() ? \ReflectionProperty::IS_PUBLIC : 0;
        $val += $this->isProtected() ? \ReflectionProperty::IS_PROTECTED : 0;
        $val += $this->isPrivate() ? \ReflectionProperty::IS_PRIVATE : 0;
        return $val;
    }

    /**
     * Get the declaring class
     *
     * @return ReflectionClass
     */
    public function getDeclaringClass() : ReflectionClass
    {
        return $this->owner;
    }

    /**
     * Returns the doc comment for this constant
     *
     * @return string
     */
    public function getDocComment() : string
    {
        return GetFirstDocComment::forNode($this->node);
    }

    /**
     * Returns the constant visibility
     *
     * @return string
     */
    private function getVisibility() : string
    {
        if ($this->isPrivate()) {
            return 'private';
        }
        if ($this->isProtected()) {
            return 'protected';
        }
        // default visibility always is public
        return 'public';
    }

    /**
     * To string
     *
     * @link http://php.net/manual/en/reflector.tostring.php
     * @return string
     * @since 5.0
     */
    public function __toString()
    {
        $value = $this->getValue();

        return sprintf(
            'Constant [ %s %s %s ] { %s }' . PHP_EOL,
            $this->getVisibility(),
            gettype($value),
            $this->getName(),
            $value
        );
    }

    /**
     * Exports
     *
     * @link http://php.net/manual/en/reflector.export.php
     * @return string
     * @since 5.0
     */
    public static function export()
    {
        throw new \Exception('Unable to export statically');
    }
}
