<?php

namespace Asgrim;

use PhpParser\Node\Stmt\ClassMethod as MethodNode;

abstract class ReflectionFunctionAbstract
{
    /**
     * @var ReflectionParameter[]
     */
    private $parameters;

    /**
     * @var string
     */
    private $name;

    protected function __construct()
    {
        $this->parameters = [];
    }

    protected function populateFunctionAbstract(MethodNode $node)
    {
        $this->name = $node->name;

        foreach ($node->params as $paramNode) {
            $this->parameters[] = ReflectionParameter::createFromNode($paramNode, $this);
        }
    }

    /**
     * Get the name of this function or method
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the number of parameters for this class
     *
     * @return int
     */
    public function getNumberOfParameters()
    {
        return count($this->parameters);
    }

    /**
     * Get the number of required parameters for this method
     *
     * @return int
     */
    public function getNumberOfRequiredParameters()
    {
        return count(array_filter($this->parameters, function (ReflectionParameter $p) {
            return !$p->isOptional();
        }));
    }

    /**
     * Get an array list of the parameters for this method signature, as an array of ReflectionParameter instances
     *
     * @return ReflectionParameter[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
