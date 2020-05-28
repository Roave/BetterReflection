<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use InvalidArgumentException;
use PhpParser\Builder\Property as PropertyNodeBuilder;
use PhpParser\Node\Stmt\ClassLike as ClassLikeNode;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property as PropertyNode;
use ReflectionException;
use ReflectionObject as CoreReflectionObject;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\AnonymousClassObjectSourceLocator;
use function array_merge;
use function get_class;
use function is_object;
use function strpos;

class ReflectionObject extends ReflectionClass
{
    /** @var ReflectionClass */
    private $reflectionClass;

    /** @var object */
    private $object;

    /** @var Reflector */
    private $reflector;

    /**
     * @param object $object
     */
    private function __construct(Reflector $reflector, ReflectionClass $reflectionClass, $object)
    {
        $this->reflector       = $reflector;
        $this->reflectionClass = $reflectionClass;
        $this->object          = $object;
    }

    /**
     * Pass an instance of an object to this method to reflect it
     *
     * @param object $object
     *
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws IdentifierNotFound
     *
     * @psalm-suppress DocblockTypeContradiction
     */
    public static function createFromInstance($object) : ReflectionClass
    {
        if (! is_object($object)) {
            throw new InvalidArgumentException('Can only create from an instance of an object');
        }

        $className = get_class($object);

        if (strpos($className, ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX) === 0) {
            $reflector = new ClassReflector(new AnonymousClassObjectSourceLocator(
                $object,
                (new BetterReflection())->phpParser()
            ));
        } else {
            $reflector = (new BetterReflection())->classReflector();
        }

        return new self($reflector, $reflector->reflect($className), $object);
    }

    /**
     * Reflect on runtime properties for the current instance
     *
     * @see ReflectionClass::getProperties() for the usage of $filter
     *
     * @return array<string, ReflectionProperty>
     */
    private function getRuntimeProperties(?int $filter = null) : array
    {
        if (! $this->reflectionClass->isInstance($this->object)) {
            throw new InvalidArgumentException('Cannot reflect runtime properties of a separate class');
        }

        // Ensure we have already cached existing properties so we can add to them
        $this->reflectionClass->getProperties();

        // Only known current way is to use internal ReflectionObject to get
        // the runtime-declared properties  :/
        $reflectionProperties = (new CoreReflectionObject($this->object))->getProperties();
        $runtimeProperties    = [];

        foreach ($reflectionProperties as $property) {
            if ($this->reflectionClass->hasProperty($property->getName())) {
                continue;
            }

            $reflectionProperty = $this->reflectionClass->getProperty($property->getName());

            $runtimeProperty = ReflectionProperty::createFromNode(
                $this->reflector,
                $this->createPropertyNodeFromReflection($property, $this->object),
                0,
                $reflectionProperty
                    ? $reflectionProperty->getDeclaringClass()->getDeclaringNamespaceAst()
                    : null,
                $this,
                $this,
                false
            );

            if ($filter !== null && ! ($filter & $runtimeProperty->getModifiers())) {
                continue;
            }

            $runtimeProperties[$runtimeProperty->getName()] = $runtimeProperty;
        }

        return $runtimeProperties;
    }

    /**
     * Create an AST PropertyNode given a reflection
     *
     * Note that we don't copy across DocBlock, protected, private or static
     * because runtime properties can't have these attributes.
     *
     * @param object $instance
     */
    private function createPropertyNodeFromReflection(CoreReflectionProperty $property, $instance) : PropertyNode
    {
        $builder = new PropertyNodeBuilder($property->getName());
        $builder->setDefault($property->getValue($instance));

        if ($property->isPublic()) {
            $builder->makePublic();
        }

        return $builder->getNode();
    }

    public function getShortName() : string
    {
        return $this->reflectionClass->getShortName();
    }

    public function getName() : string
    {
        return $this->reflectionClass->getName();
    }

    public function getNamespaceName() : string
    {
        return $this->reflectionClass->getNamespaceName();
    }

    public function inNamespace() : bool
    {
        return $this->reflectionClass->inNamespace();
    }

    public function getExtensionName() : ?string
    {
        return $this->reflectionClass->getExtensionName();
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

    public function getMethod(string $methodName) : ReflectionMethod
    {
        return $this->reflectionClass->getMethod($methodName);
    }

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

    public function hasConstant(string $name) : bool
    {
        return $this->reflectionClass->hasConstant($name);
    }

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

    public function getConstructor() : ReflectionMethod
    {
        return $this->reflectionClass->getConstructor();
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(?int $filter = null) : array
    {
        return array_merge(
            $this->reflectionClass->getProperties($filter),
            $this->getRuntimeProperties($filter)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getImmediateProperties(?int $filter = null) : array
    {
        return array_merge(
            $this->reflectionClass->getImmediateProperties($filter),
            $this->getRuntimeProperties($filter)
        );
    }

    public function getProperty(string $name) : ?ReflectionProperty
    {
        $runtimeProperties = $this->getRuntimeProperties();

        if (isset($runtimeProperties[$name])) {
            return $runtimeProperties[$name];
        }

        return $this->reflectionClass->getProperty($name);
    }

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

    public function getFileName() : ?string
    {
        return $this->reflectionClass->getFileName();
    }

    public function getLocatedSource() : LocatedSource
    {
        return $this->reflectionClass->getLocatedSource();
    }

    public function getStartLine() : int
    {
        return $this->reflectionClass->getStartLine();
    }

    public function getEndLine() : int
    {
        return $this->reflectionClass->getEndLine();
    }

    public function getStartColumn() : int
    {
        return $this->reflectionClass->getStartColumn();
    }

    public function getEndColumn() : int
    {
        return $this->reflectionClass->getEndColumn();
    }

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

    public function getDocComment() : string
    {
        return $this->reflectionClass->getDocComment();
    }

    public function isAnonymous() : bool
    {
        return $this->reflectionClass->isAnonymous();
    }

    public function isInternal() : bool
    {
        return $this->reflectionClass->isInternal();
    }

    public function isUserDefined() : bool
    {
        return $this->reflectionClass->isUserDefined();
    }

    public function isAbstract() : bool
    {
        return $this->reflectionClass->isAbstract();
    }

    public function isFinal() : bool
    {
        return $this->reflectionClass->isFinal();
    }

    public function getModifiers() : int
    {
        return $this->reflectionClass->getModifiers();
    }

    public function isTrait() : bool
    {
        return $this->reflectionClass->isTrait();
    }

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

    public function isSubclassOf(string $className) : bool
    {
        return $this->reflectionClass->isSubclassOf($className);
    }

    public function implementsInterface(string $interfaceName) : bool
    {
        return $this->reflectionClass->implementsInterface($interfaceName);
    }

    public function isInstantiable() : bool
    {
        return $this->reflectionClass->isInstantiable();
    }

    public function isCloneable() : bool
    {
        return $this->reflectionClass->isCloneable();
    }

    public function isIterateable() : bool
    {
        return $this->reflectionClass->isIterateable();
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticProperties() : array
    {
        return $this->reflectionClass->getStaticProperties();
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

    public function getAst() : ClassLikeNode
    {
        return $this->reflectionClass->getAst();
    }

    public function getDeclaringNamespaceAst() : ?Namespace_
    {
        return $this->reflectionClass->getDeclaringNamespaceAst();
    }

    public function setFinal(bool $isFinal) : void
    {
        $this->reflectionClass->setFinal($isFinal);
    }

    public function removeMethod(string $methodName) : bool
    {
        return $this->reflectionClass->removeMethod($methodName);
    }

    public function addMethod(string $methodName) : void
    {
        $this->reflectionClass->addMethod($methodName);
    }

    public function removeProperty(string $methodName) : bool
    {
        return $this->reflectionClass->removeProperty($methodName);
    }

    public function addProperty(
        string $methodName,
        int $visibility = CoreReflectionProperty::IS_PUBLIC,
        bool $static = false
    ) : void {
        $this->reflectionClass->addProperty($methodName, $visibility, $static);
    }
}
