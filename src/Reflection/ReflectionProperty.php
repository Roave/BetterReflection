<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Closure;
use Exception;
use InvalidArgumentException;
use phpDocumentor\Reflection\Type;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property as PropertyNode;
use ReflectionProperty as CoreReflectionProperty;
use Reflector as CoreReflector;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\StringCast\ReflectionPropertyStringCast;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\TypesFinder\FindPropertyType;
use Roave\BetterReflection\Util\CalculateReflectionColum;
use Roave\BetterReflection\Util\GetFirstDocComment;
use function class_exists;
use function func_num_args;
use function get_class;
use function is_object;

class ReflectionProperty implements CoreReflector
{
    /** @var ReflectionClass */
    private $declaringClass;

    /** @var ReflectionClass */
    private $implementingClass;

    /** @var PropertyNode */
    private $node;

    /** @var int */
    private $positionInNode;

    /** @var Namespace_|null */
    private $declaringNamespace;

    /** @var bool */
    private $declaredAtCompileTime = true;

    /** @var Reflector */
    private $reflector;

    private function __construct()
    {
    }

    public static function export() : void
    {
        throw new Exception('Unable to export statically');
    }

    /**
     * Create a reflection of a class's property by its name
     */
    public static function createFromName(string $className, string $propertyName) : self
    {
        return ReflectionClass::createFromName($className)->getProperty($propertyName);
    }

    /**
     * Create a reflection of an instance's property by its name
     *
     * @param object $instance
     *
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     * @throws IdentifierNotFound
     */
    public static function createFromInstance($instance, string $propertyName) : self
    {
        return ReflectionClass::createFromInstance($instance)->getProperty($propertyName);
    }

    public function __toString() : string
    {
        return ReflectionPropertyStringCast::toString($this);
    }

    /**
     * @internal
     * @param PropertyNode $node Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     */
    public static function createFromNode(
        Reflector $reflector,
        PropertyNode $node,
        int $positionInNode,
        ?Namespace_ $declaringNamespace,
        ReflectionClass $declaringClass,
        ReflectionClass $implementingClass,
        bool $declaredAtCompileTime = true
    ) : self {
        $prop                        = new self();
        $prop->reflector             = $reflector;
        $prop->node                  = $node;
        $prop->positionInNode        = $positionInNode;
        $prop->declaringNamespace    = $declaringNamespace;
        $prop->declaringClass        = $declaringClass;
        $prop->implementingClass     = $implementingClass;
        $prop->declaredAtCompileTime = $declaredAtCompileTime;

        return $prop;
    }

    /**
     * Set the default visibility of this property. Use the core \ReflectionProperty::IS_* values as parameters, e.g.:
     *
     * @throws \InvalidArgumentException
     */
    public function setVisibility(int $newVisibility) : void
    {
        $this->node->flags &= ~Class_::MODIFIER_PRIVATE & ~Class_::MODIFIER_PROTECTED & ~Class_::MODIFIER_PUBLIC;

        switch ($newVisibility) {
            case CoreReflectionProperty::IS_PRIVATE:
                $this->node->flags |= Class_::MODIFIER_PRIVATE;
                break;
            case CoreReflectionProperty::IS_PROTECTED:
                $this->node->flags |= Class_::MODIFIER_PROTECTED;
                break;
            case CoreReflectionProperty::IS_PUBLIC:
                $this->node->flags |= Class_::MODIFIER_PUBLIC;
                break;
            default:
                throw new InvalidArgumentException('Visibility should be \ReflectionProperty::IS_PRIVATE, ::IS_PROTECTED or ::IS_PUBLIC constants');
        }
    }

    /**
     * Has the property been declared at compile-time?
     *
     * Note that unless the property is static, this is hard coded to return
     * true, because we are unable to reflect instances of classes, therefore
     * we can be sure that all properties are always declared at compile-time.
     */
    public function isDefault() : bool
    {
        return $this->declaredAtCompileTime;
    }

    /**
     * Get the core-reflection-compatible modifier values.
     */
    public function getModifiers() : int
    {
        $val  = 0;
        $val += $this->isStatic() ? CoreReflectionProperty::IS_STATIC : 0;
        $val += $this->isPublic() ? CoreReflectionProperty::IS_PUBLIC : 0;
        $val += $this->isProtected() ? CoreReflectionProperty::IS_PROTECTED : 0;
        $val += $this->isPrivate() ? CoreReflectionProperty::IS_PRIVATE : 0;
        return $val;
    }

    /**
     * Get the name of the property.
     */
    public function getName() : string
    {
        return $this->node->props[$this->positionInNode]->name->name;
    }

    /**
     * Is the property private?
     */
    public function isPrivate() : bool
    {
        return $this->node->isPrivate();
    }

    /**
     * Is the property protected?
     */
    public function isProtected() : bool
    {
        return $this->node->isProtected();
    }

    /**
     * Is the property public?
     */
    public function isPublic() : bool
    {
        return $this->node->isPublic();
    }

    /**
     * Is the property static?
     */
    public function isStatic() : bool
    {
        return $this->node->isStatic();
    }

    /**
     * Get the DocBlock type hints as an array of strings.
     *
     * @return string[]
     */
    public function getDocBlockTypeStrings() : array
    {
        $stringTypes = [];

        foreach ($this->getDocBlockTypes() as $type) {
            $stringTypes[] = (string) $type;
        }
        return $stringTypes;
    }

    /**
     * Get the types defined in the DocBlocks. This returns an array because
     * the parameter may have multiple (compound) types specified (for example
     * when you type hint pipe-separated "string|null", in which case this
     * would return an array of Type objects, one for string, one for null.
     *
     * @return Type[]
     */
    public function getDocBlockTypes() : array
    {
        return (new FindPropertyType())->__invoke($this, $this->declaringNamespace);
    }

    public function getDeclaringClass() : ReflectionClass
    {
        return $this->declaringClass;
    }

    public function getImplementingClass() : ReflectionClass
    {
        return $this->implementingClass;
    }

    public function getDocComment() : string
    {
        return GetFirstDocComment::forNode($this->node);
    }

    /**
     * Get the default value of the property (as defined before constructor is
     * called, when the property is defined)
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        $defaultValueNode = $this->node->props[$this->positionInNode]->default;

        if ($defaultValueNode === null) {
            return null;
        }

        return (new CompileNodeToValue())->__invoke(
            $defaultValueNode,
            new CompilerContext($this->reflector, $this->getDeclaringClass())
        );
    }

    /**
     * Get the line number that this property starts on.
     */
    public function getStartLine() : int
    {
        return $this->node->getStartLine();
    }

    /**
     * Get the line number that this property ends on.
     */
    public function getEndLine() : int
    {
        return $this->node->getEndLine();
    }

    public function getStartColumn() : int
    {
        return CalculateReflectionColum::getStartColumn($this->declaringClass->getLocatedSource()->getSource(), $this->node);
    }

    public function getEndColumn() : int
    {
        return CalculateReflectionColum::getEndColumn($this->declaringClass->getLocatedSource()->getSource(), $this->node);
    }

    public function getAst() : PropertyNode
    {
        return $this->node;
    }

    public function getPositionInAst() : int
    {
        return $this->positionInNode;
    }

    /**
     * {@inheritdoc}
     * @throws Uncloneable
     */
    public function __clone()
    {
        throw Uncloneable::fromClass(__CLASS__);
    }

    /**
     * @param object|null $object
     *
     * @return mixed
     *
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws NotAnObject
     * @throws ObjectNotInstanceOfClass
     */
    public function getValue($object = null)
    {
        $declaringClassName = $this->getDeclaringClass()->getName();

        if ($this->isStatic()) {
            $this->assertClassExist($declaringClassName);

            return Closure::bind(function (string $declaringClassName, string $propertyName) {
                return $declaringClassName::${$propertyName};
            }, null, $declaringClassName)->__invoke($declaringClassName, $this->getName());
        }

        $instance = $this->assertObject($object);

        return Closure::bind(function ($instance, string $propertyName) {
            return $instance->{$propertyName};
        }, $instance, $declaringClassName)->__invoke($instance, $this->getName());
    }

    /**
     * @param object     $object
     *
     * @param mixed|null $value
     *
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws NotAnObject
     * @throws ObjectNotInstanceOfClass
     */
    public function setValue($object, $value = null) : void
    {
        $declaringClassName = $this->getDeclaringClass()->getName();

        if ($this->isStatic()) {
            $this->assertClassExist($declaringClassName);

            Closure::bind(function (string $declaringClassName, string $propertyName, $value) : void {
                $declaringClassName::${$propertyName} = $value;
            }, null, $declaringClassName)->__invoke($declaringClassName, $this->getName(), func_num_args() === 2 ? $value : $object);

            return;
        }

        $instance = $this->assertObject($object);

        Closure::bind(function ($instance, string $propertyName, $value) : void {
            $instance->{$propertyName} = $value;
        }, $instance, $declaringClassName)->__invoke($instance, $this->getName(), $value);
    }

    /**
     * @throws ClassDoesNotExist
     */
    private function assertClassExist(string $className) : void
    {
        if (! class_exists($className, false)) {
            throw new ClassDoesNotExist('Property cannot be retrieved as the class is not loaded');
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
        if ($object === null) {
            throw NoObjectProvided::create();
        }

        if (! is_object($object)) {
            throw NotAnObject::fromNonObject($object);
        }

        $declaringClassName = $this->getDeclaringClass()->getName();

        if (get_class($object) !== $declaringClassName) {
            throw ObjectNotInstanceOfClass::fromClassName($declaringClassName);
        }

        return $object;
    }
}
