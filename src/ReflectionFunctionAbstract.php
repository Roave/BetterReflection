<?php

namespace BetterReflection;

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

    /**
     * @var string
     */
    private $docBlock;

    /**
     * @var string
     */
    private $filename;

    protected function __construct()
    {
        $this->parameters = [];
    }

    /**
     * Populate the common elements of the function abstract
     *
     * @param MethodNode $node
     * @param string $filename
     */
    protected function populateFunctionAbstract(MethodNode $node, $filename)
    {
        $this->name = $node->name;
        $this->filename = $filename;

        if ($node->hasAttribute('comments')) {
            /* @var \PhpParser\Comment\Doc $comment */
            $comment = $node->getAttribute('comments')[0];
            $this->docBlock = $comment->getReformattedText();
        }

        // We must determine if params are optional or not ahead of time, but
        // we must do it in reverse...
        $overallOptionalFlag = true;
        for ($i = count($node->params) - 1; $i >= 0; $i--) {
            $hasDefault = ($node->params[$i]->default !== null);

            // When we find the first parameter that does not have a default,
            // flip the flag as all params for this are no longer optional
            // EVEN if they have a default value
            if (!$hasDefault) {
                $overallOptionalFlag = false;
            }

            $node->params[$i]->isOptional = $overallOptionalFlag;
        }

        foreach ($node->params as $paramIndex => $paramNode) {
            $this->parameters[] = ReflectionParameter::createFromNode($paramNode, $this, $paramIndex);
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

    /**
     * Get a single parameter by name. Returns null if parameter not found for the function
     *
     * @param string $parameterName
     * @return ReflectionParameter|null
     */
    public function getParameter($parameterName)
    {
        foreach ($this->parameters as $parameter) {
            if ($parameter->getName() == $parameterName) {
                return $parameter;
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getDocComment()
    {
        return $this->docBlock;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->filename;
    }
}
