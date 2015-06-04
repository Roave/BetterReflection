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

    public function getShortName()
    {
        return $this->name;
    }

    public function getName()
    {
        if (!$this->inNamespace()) {
            return $this->getShortName();
        }

        return $this->getNamespaceName() . '\\' . $this->getShortName();
    }

    public function getNamespaceName()
    {
        if (!$this->inNamespace()) {
            return '';
        }

        return implode('\\', $this->declaringNamespace->name->parts);
    }

    /**
     * @return bool
     */
    public function inNamespace()
    {
        return !(is_null($this->declaringNamespace)) && !is_null($this->declaringNamespace->name);
    }

    /**
     * @return ReflectionMethod[]
     */
    public function getMethods()
    {
        return $this->methods;
    }
}
