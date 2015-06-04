<?php

namespace Asgrim;

use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use PhpParser\Node\Stmt\Class_ as ClassNode;

class ReflectionClass
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var NamespaceNode
     */
    private $declaringNamespace;

    /**
     * @var ReflectionMethod[]
     */
    private $methods;

    private function __construct()
    {
        $this->declaringNamespace = null;
        $this->methods = [];
    }

    /**
     * Create from a Class Node
     *
     * @param ClassNode $node
     * @param NamespaceNode $namespace optional - if omitted, we assume it is global namespaced class
     * @return ReflectionClass
     */
    public static function createFromNode(ClassNode $node, NamespaceNode $namespace = null)
    {
        $class = new self();

        $class->name = $node->name;

        if (null !== $namespace) {
            $class->declaringNamespace = $namespace;
        }

        $methodNodes = $node->getMethods();

        foreach ($methodNodes as $methodNode) {
            $class->methods[] = ReflectionMethod::createFromNode($methodNode, $class);
        }

        return $class;
    }

    /**
     * Get the "short" name of the class (e.g. for A\B\Foo, this will return "Foo")
     *
     * @return string
     */
    public function getShortName()
    {
        return $this->name;
    }

    /**
     * Get the "full" name of the class (e.g. for A\B\Foo, this will return "A\B\Foo")
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
     * Get the "namespace" name of the class (e.g. for A\B\Foo, this will return "A\B")
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
     * Decide if this class is part of a namespace. Returns false if global namespace;
     *
     * @return bool
     */
    public function inNamespace()
    {
        return !(is_null($this->declaringNamespace)) && !is_null($this->declaringNamespace->name);
    }

    /**
     * Fetch an array of all methods for this class
     *
     * @return ReflectionMethod[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Get a single method with the name $methodName
     *
     * @param string $methodName
     * @return ReflectionMethod
     */
    public function getMethod($methodName)
    {
        foreach ($this->getMethods() as $method) {
            if ($method->getName() == $methodName) {
                return $method;
            }
        }

        throw new \OutOfBoundsException('Could not find method: ' . $methodName);
    }
}
