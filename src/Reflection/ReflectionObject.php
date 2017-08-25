<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Reflector;
use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\Builder\Property as PropertyNodeBuilder;
use PhpParser\Node\Stmt\ClassLike as ClassLikeNode;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\AnonymousClassObjectSourceLocator;

class ReflectionObject extends ReflectionClass
{
    /**
     * @var ReflectionClass
     */
    private $reflectionClass;

    /**
     * @var object
     */
    private $object;

    /**
     * @var Reflector
     */
    private $reflector;

    private function __construct(Reflector $reflector, ReflectionClass $reflectionClass, $object)
    {
        $this->reflector = $reflector;
        $this->reflectionClass = $reflectionClass;
        $this->object = $object;
    }

    /**
     * Create a reflection and return the string representation of a class instance
     *
     * @param object $instance
     * @return string
     */
    public static function export($instance = null) : string
    {
        if (null === $instance) {
            throw new \InvalidArgumentException('Class instance must be provided');
        }

        $reflection = self::createFromInstance($instance);
        return $reflection->__toString();
    }

    /**
     * Cannot instantiate this way, use ReflectionObject::createFromInstance
     *
     * @throws \LogicException
     */
    public static function createFromNode(
        Reflector $reflector,
        ClassLikeNode $node,
        LocatedSource $locatedSource,
        NamespaceNode $namespace = null
    ) : ReflectionClass {
        throw new \LogicException('Cannot create a ReflectionObject from node - use ReflectionObject::createFromInstance');
    }

    /**
     * Cannot instantiate this way, use ReflectionObject::createFromInstance
     *
     * @throws \LogicException
     */
    public static function createFromName(string $className) : ReflectionClass
    {
        throw new \LogicException('Cannot create a ReflectionObject from name - use ReflectionObject::createFromInstance');
    }

    /**
     * Pass an instance of an object to this method to reflect it
     *
     * @param object $object
     *
     * @return self
     *
     * @throws \InvalidArgumentException
     * @throws \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound
     */
    public static function createFromInstance($object) : ReflectionClass
    {
        if (! \is_object($object)) {
            throw new \InvalidArgumentException('Can only create from an instance of an object');
        }

        $className = \get_class($object);

        if (\strpos($className, ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX) === 0) {
            $reflector = new ClassReflector(new AnonymousClassObjectSourceLocator($object));
        } else {
            $reflector = ClassReflector::buildDefaultReflector();
        }

        $reflectionClass = $reflector->reflect($className);

        return new self($reflector, $reflectionClass, $object);
    }

    /**
     * Reflect on runtime properties for the current instance
     *
     * @param int|null $filter
     * @see ReflectionClass::getProperties() for the usage of $filter
     * @return ReflectionProperty[]
     */
    private function getRuntimeProperties(?int $filter = null) : array
    {
        if (!$this->reflectionClass->isInstance($this->object)) {
            throw new \InvalidArgumentException('Cannot reflect runtime properties of a separate class');
        }

        // Ensure we have already cached existing properties so we can add to them
        $this->reflectionClass->getProperties();

        // Only known current way is to use internal ReflectionObject to get
        // the runtime-declared properties  :/
        $reflectionProperties = (new \ReflectionObject($this->object))->getProperties();
        $runtimeProperties = [];
        foreach ($reflectionProperties as $property) {
            if ($this->reflectionClass->hasProperty($property->getName())) {
                continue;
            }

            $runtimeProperty = ReflectionProperty::createFromNode(
                $this->reflector,
                $this->createPropertyNodeFromReflection($property, $this->object),
                $this,
                false
            );

            if (null === $filter || $filter & $runtimeProperty->getModifiers()) {
                $runtimeProperties[$runtimeProperty->getName()] = $runtimeProperty;
            }
        }
        return $runtimeProperties;
    }

    /**
     * Create an AST PropertyNode given a reflection
     *
     * Note that we don't copy across DocBlock, protected, private or static
     * because runtime properties can't have these attributes.
     *
     * @param \ReflectionProperty $property
     * @param object $instance
     * @return PropertyNode
     */
    private function createPropertyNodeFromReflection(\ReflectionProperty $property, $instance) : PropertyNode
    {
        $builder = new PropertyNodeBuilder($property->getName());
        $builder->setDefault($property->getValue($instance));

        if ($property->isPublic()) {
            $builder->makePublic();
        }

        return $builder->getNode();
    }

    /**
     * {@inheritdoc}
     */
    public function getShortName() : string
    {
        return $this->reflectionClass->getShortName();
    }

    /**
     * {@inheritdoc}
     */
    public function getName() : string
    {
        return $this->reflectionClass->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespaceName() : string
    {
        return $this->reflectionClass->getNamespaceName();
    }

    /**
     * {@inheritdoc}
     */
    public function inNamespace() : bool
    {
        return $this->reflectionClass->inNamespace();
    }

    /**
     * {@inheritdoc}
     */
    public function getMethods(?int $filter = null) : array
    {
        return $this->reflectionClass->getMethods($filter);
    }

    /**
     * {@inheritdoc}
     */
    public function getImmediateMethods(?int $filter = null) : array
    {
        return $this->reflectionClass->getImmediateMethods($filter);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(string $methodName) : ReflectionMethod
    {
        return $this->reflectionClass->getMethod($methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasMethod(string $methodName) : bool
    {
        return $this->reflectionClass->hasMethod($methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function getImmediateConstants() : array
    {
        return $this->reflectionClass->getImmediateConstants();
    }

    /**
     * {@inheritdoc}
     */
    public function getConstants() : array
    {
        return $this->reflectionClass->getConstants();
    }

    /**
     * {@inheritdoc}
     */
    public function getConstant(string $name)
    {
        return $this->reflectionClass->getConstant($name);
    }

    /**
     * {@inheritdoc}
     */
    public function hasConstant(string $name) : bool
    {
        return $this->reflectionClass->hasConstant($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getReflectionConstant(string $name) : ?ReflectionClassConstant
    {
        return $this->reflectionClass->getReflectionConstant($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getImmediateReflectionConstants() : array
    {
        return $this->reflectionClass->getImmediateReflectionConstants();
    }

    /**
     * {@inheritdoc}
     */
    public function getReflectionConstants() : array
    {
        return $this->reflectionClass->getReflectionConstants();
    }

    /**
     * {@inheritdoc}
     */
    public function getConstructor() : ReflectionMethod
    {
        return $this->reflectionClass->getConstructor();
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(?int $filter = null) : array
    {
        return \array_merge(
            $this->reflectionClass->getProperties($filter),
            $this->getRuntimeProperties($filter)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getImmediateProperties(?int $filter = null): array
    {
        return \array_merge(
            $this->reflectionClass->getImmediateProperties($filter),
            $this->getRuntimeProperties($filter)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getProperty(string $name) : ?ReflectionProperty
    {
        $runtimeProperties = $this->getRuntimeProperties();

        if (isset($runtimeProperties[$name])) {
            return $runtimeProperties[$name];
        }

        return $this->reflectionClass->getProperty($name);
    }

    /**
     * {@inheritdoc}
     */
    public function hasProperty(string $name) : bool
    {
        $runtimeProperties = $this->getRuntimeProperties();

        return isset($runtimeProperties[$name]) || $this->reflectionClass->hasProperty($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultProperties() : array
    {
        return $this->reflectionClass->getDefaultProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function getFileName() : ?string
    {
        return $this->reflectionClass->getFileName();
    }

    /**
     * {@inheritdoc}
     */
    public function getLocatedSource() : LocatedSource
    {
        return $this->reflectionClass->getLocatedSource();
    }

    /**
     * {@inheritdoc}
     */
    public function getStartLine() : int
    {
        return $this->reflectionClass->getStartLine();
    }

    /**
     * {@inheritdoc}
     */
    public function getEndLine() : int
    {
        return $this->reflectionClass->getEndLine();
    }

    /**
     * {@inheritdoc}
     */
    public function getStartColumn() : int
    {
        return $this->reflectionClass->getStartColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function getEndColumn() : int
    {
        return $this->reflectionClass->getEndColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function getParentClass() : ?ReflectionClass
    {
        return $this->reflectionClass->getParentClass();
    }

    /**
     * {@inheritdoc}
     */
    public function getParentClassNames() : array
    {
        return $this->reflectionClass->getParentClassNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getDocComment() : string
    {
        return $this->reflectionClass->getDocComment();
    }

    /**
     * {@inheritdoc}
     */
    public function isAnonymous() : bool
    {
        return $this->reflectionClass->isAnonymous();
    }

    /**
     * {@inheritdoc}
     */
    public function isInternal() : bool
    {
        return $this->reflectionClass->isInternal();
    }

    /**
     * {@inheritdoc}
     */
    public function isUserDefined() : bool
    {
        return $this->reflectionClass->isUserDefined();
    }

    /**
     * {@inheritdoc}
     */
    public function isAbstract() : bool
    {
        return $this->reflectionClass->isAbstract();
    }

    /**
     * {@inheritdoc}
     */
    public function isFinal() : bool
    {
        return $this->reflectionClass->isFinal();
    }

    /**
     * {@inheritdoc}
     */
    public function getModifiers() : int
    {
        return $this->reflectionClass->getModifiers();
    }

    /**
     * {@inheritdoc}
     */
    public function isTrait() : bool
    {
        return $this->reflectionClass->isTrait();
    }

    /**
     * {@inheritdoc}
     */
    public function isInterface() : bool
    {
        return $this->reflectionClass->isInterface();
    }

    /**
     * {@inheritdoc}
     */
    public function getTraits() : array
    {
        return $this->reflectionClass->getTraits();
    }

    /**
     * {@inheritdoc}
     */
    public function getTraitNames() : array
    {
        return $this->reflectionClass->getTraitNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getTraitAliases() : array
    {
        return $this->reflectionClass->getTraitAliases();
    }

    /**
     * {@inheritdoc}
     */
    public function getInterfaces() : array
    {
        return $this->reflectionClass->getInterfaces();
    }

    /**
     * {@inheritdoc}
     */
    public function getImmediateInterfaces() : array
    {
        return $this->reflectionClass->getImmediateInterfaces();
    }

    /**
     * {@inheritdoc}
     */
    public function getInterfaceNames() : array
    {
        return $this->reflectionClass->getInterfaceNames();
    }

    /**
     * {@inheritdoc}
     */
    public function isInstance($object) : bool
    {
        return $this->reflectionClass->isInstance($object);
    }

    /**
     * {@inheritdoc}
     */
    public function isSubclassOf(string $className) : bool
    {
        return $this->reflectionClass->isSubclassOf($className);
    }

    /**
     * {@inheritdoc}
     */
    public function implementsInterface(string $interfaceName) : bool
    {
        return $this->reflectionClass->implementsInterface($interfaceName);
    }

    /**
     * {@inheritdoc}
     */
    public function isInstantiable() : bool
    {
        return $this->reflectionClass->isInstantiable();
    }

    /**
     * {@inheritdoc}
     */
    public function isCloneable() : bool
    {
        return $this->reflectionClass->isCloneable();
    }

    /**
     * {@inheritdoc}
     */
    public function isIterateable() : bool
    {
        return $this->reflectionClass->isIterateable();
    }

    /**
     * {@inheritdoc}
     */
    public function setStaticPropertyValue(string $propertyName, $value) : void
    {
        $this->reflectionClass->setStaticPropertyValue($propertyName, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticPropertyValue(string $propertyName)
    {
        return $this->reflectionClass->getStaticPropertyValue($propertyName);
    }

    /**
     * {@inheritdoc}
     */
    public function getAst() : ClassLikeNode
    {
        return $this->reflectionClass->getAst();
    }

    /**
     * {@inheritdoc}
     */
    public function setFinal(bool $isFinal) : void
    {
        $this->reflectionClass->setFinal($isFinal);
    }

    /**
     * {@inheritdoc}
     */
    public function removeMethod(string $methodName) : bool
    {
        return $this->reflectionClass->removeMethod($methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function addMethod(string $methodName) : void
    {
        $this->reflectionClass->addMethod($methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function removeProperty(string $methodName) : bool
    {
        return $this->reflectionClass->removeProperty($methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function addProperty(
        string $methodName,
        int $visibility = \ReflectionProperty::IS_PUBLIC,
        bool $static = false
    ) : void {
        $this->reflectionClass->addProperty($methodName, $visibility, $static);
    }
}
