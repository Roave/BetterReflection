<?php

namespace BetterReflection\Reflection;

use BetterReflection\NodeCompiler\CompileNodeToValue;
use BetterReflection\Reflection\Exception\NoParent;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\AutoloadSourceLocator;
use BetterReflection\SourceLocator\LocatedSource;
use BetterReflection\SourceLocator\SourceLocator;
use BetterReflection\TypesFinder\FindTypeFromAst;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Types\Object_;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use PhpParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\Node\Stmt\ClassConst as ConstNode;
use PhpParser\Node\Stmt\Property as PropertyNode;

class ReflectionClass implements Reflection
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var NamespaceNode
     */
    private $declaringNamespace = null;

    /**
     * @var ReflectionMethod[]
     */
    private $methods = [];

    /**
     * @var mixed[]
     */
    private $constants = [];

    /**
     * @var ReflectionProperty[]
     */
    private $properties = [];

    /**
     * @var LocatedSource
     */
    private $locatedSource;

    /**
     * @var Fqsen|null
     */
    private $extendsClassType;

    private function __construct()
    {
    }

    public static function createFromName($className)
    {
        return (new ClassReflector(new AutoloadSourceLocator()))->reflect($className);
    }

    /**
     * Create from a Class Node.
     *
     * @param ClassNode $node
     * @param LocatedSource $locatedSource
     * @param NamespaceNode|null $namespace optional - if omitted, we assume it is global namespaced class
     * @return ReflectionClass
     */
    public static function createFromNode(
        ClassNode $node,
        LocatedSource $locatedSource,
        NamespaceNode $namespace = null
    ) {
        $class = new self();

        $class->locatedSource = $locatedSource;
        $class->name = $node->name;

        if (null !== $namespace) {
            $class->declaringNamespace = $namespace;
        }

        if (null !== $node->extends) {
            $objectType = (new FindTypeFromAst())->__invoke($node->extends, $locatedSource, $class->getNamespaceName());
            if (null !== $objectType && $objectType instanceof Object_) {
                $class->extendsClassType = $objectType->getFqsen();
            }
        }

        $methodNodes = $node->getMethods();

        foreach ($methodNodes as $methodNode) {
            $class->methods[] = ReflectionMethod::createFromNode(
                $methodNode,
                $class
            );
        }

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ConstNode) {
                $constName = $stmt->consts[0]->name;
                $constValue = (new CompileNodeToValue())->__invoke($stmt->consts[0]->value);
                $class->constants[$constName] = $constValue;
            }

            if ($stmt instanceof PropertyNode) {
                $prop = ReflectionProperty::createFromNode($stmt, $class);
                $class->properties[$prop->getName()] = $prop;
            }
        }

        return $class;
    }

    /**
     * Get the "short" name of the class (e.g. for A\B\Foo, this will return
     * "Foo").
     *
     * @return string
     */
    public function getShortName()
    {
        return $this->name;
    }

    /**
     * Get the "full" name of the class (e.g. for A\B\Foo, this will return
     * "A\B\Foo").
     *
     * @return string
     */
    public function getName()
    {
        if (!$this->inNamespace()) {
            return $this->getShortName();
        }

        return $this->getNamespaceName() . '\\' . $this->getShortName();
    }

    /**
     * Get the "namespace" name of the class (e.g. for A\B\Foo, this will
     * return "A\B").
     *
     * @return string
     */
    public function getNamespaceName()
    {
        if (!$this->inNamespace()) {
            return '';
        }

        return implode('\\', $this->declaringNamespace->name->parts);
    }

    /**
     * Decide if this class is part of a namespace. Returns false if the class
     * is in the global namespace or does not have a specified namespace.
     *
     * @return bool
     */
    public function inNamespace()
    {
        return null !== $this->declaringNamespace
            && null !== $this->declaringNamespace->name;
    }

    /**
     * Fetch an array of all methods for this class.
     *
     * @return ReflectionMethod[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Get a single method with the name $methodName.
     *
     * @param string $methodName
     * @return ReflectionMethod
     */
    public function getMethod($methodName)
    {
        foreach ($this->getMethods() as $method) {
            if ($method->getName() === $methodName) {
                return $method;
            }
        }

        throw new \OutOfBoundsException(
            'Could not find method: ' . $methodName
        );
    }

    /**
     * Get an array of the defined constants in this class.
     *
     * @return mixed[]
     */
    public function getConstants()
    {
        return $this->constants;
    }

    /**
     * Get the value of the specified class constant.
     *
     * Returns null if not specified.
     *
     * @param string $name
     * @return mixed|null
     */
    public function getConstant($name)
    {
        if (!isset($this->constants[$name])) {
            return null;
        }

        return $this->constants[$name];
    }

    /**
     * Get the constructor method for this class.
     *
     * @return ReflectionMethod
     */
    public function getConstructor()
    {
        return $this->getMethod('__construct');
    }

    /**
     * Get the properties for this class.
     *
     * @return ReflectionProperty[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Get the property called $name.
     *
     * Returns null if property does not exist.
     *
     * @param string $name
     * @return ReflectionProperty|null
     */
    public function getProperty($name)
    {
        if (!isset($this->properties[$name])) {
            return null;
        }

        return $this->properties[$name];
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->locatedSource->getFileName();
    }

    /**
     * @return LocatedSource
     */
    public function getLocatedSource()
    {
        return $this->locatedSource;
    }

    /**
     * Get the parent class, if it is defined. If this class does not have a
     * specified parent class, this will throw an exception.
     *
     * You may optionally specify a source locator that will be used to locate
     * the parent class. If no source locator is given, a default will be used.
     *
     * @param SourceLocator|null $sourceLocator
     * @return ReflectionClass
     * @throws NoParent
     */
    public function getParentClass(SourceLocator $sourceLocator = null)
    {
        if (null === $this->extendsClassType) {
            throw new NoParent(sprintf(
                'Class %s does not have a defined parent class',
                $this->getName()
            ));
        }

        $fqsen = $this->extendsClassType->__toString();

        if (null !== $sourceLocator) {
            return (new ClassReflector($sourceLocator))->reflect($fqsen);
        }

        return self::createFromName($fqsen);
    }
}
