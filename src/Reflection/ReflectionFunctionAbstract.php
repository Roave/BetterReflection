<?php

namespace BetterReflection\Reflection;

use PhpParser\Node\Stmt as MethodOrFunctionNode;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;

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
     * @var NamespaceNode
     */
    private $declaringNamespace;

    /**
     * @var string
     */
    private $docBlock;

    /**
     * @var string|null
     */
    private $filename;

    protected function __construct()
    {
        $this->parameters = [];
    }

    /**
     * Populate the common elements of the function abstract
     *
     * @param MethodOrFunctionNode $node
     * @param NamespaceNode|null $declaringNamespace
     * @param string|null $filename
     */
    protected function populateFunctionAbstract(MethodOrFunctionNode $node, NamespaceNode $declaringNamespace = null, $filename = null)
    {
        $this->name = $node->name;
        $this->filename = $filename;
        $this->declaringNamespace = $declaringNamespace;

        if ($node->hasAttribute('comments')) {
            /* @var \PhpParser\Comment\Doc $comment */
            $comment = $node->getAttribute('comments')[0];
            $this->docBlock = $comment->getReformattedText();
        }

        // We must determine if params are optional or not ahead of time, but
        // we must do it in reverse...
        $overallOptionalFlag = true;
        $lastParamIndex = (count($node->params) - 1);
        for ($i = $lastParamIndex; $i >= 0; $i--) {
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
            $this->parameters[] = ReflectionParameter::createFromNode(
                $paramNode,
                $this,
                $paramIndex
            );
        }
    }

    /**
     * Get the "full" name of the function (e.g. for A\B\foo, this will return
     * "A\B\foo")
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
     * Get the "short" name of the function (e.g. for A\B\foo, this will return
     * "foo")
     *
     * @return string
     */
    public function getShortName()
    {
        return $this->name;
    }

    /**
     * Get the "namespace" name of the function (e.g. for A\B\foo, this will
     * return "A\B")
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
     * Decide if this function is part of a namespace. Returns false if the class
     * is in the global namespace or does not have a specified namespace
     *
     * @return bool
     */
    public function inNamespace()
    {
        return null !== $this->declaringNamespace
            && null !== $this->declaringNamespace->name;
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
        return count(array_filter(
            $this->parameters,
            function (ReflectionParameter $p) {
                return !$p->isOptional();
            }
        ));
    }

    /**
     * Get an array list of the parameters for this method signature, as an
     * array of ReflectionParameter instances
     *
     * @return ReflectionParameter[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Get a single parameter by name. Returns null if parameter not found for
     * the function
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

    /**
     * Is this function a closure?
     *
     * Note - we cannot reflect on closures at the moment (as there is no PHP
     * source code we can access).
     *
     * @see https://github.com/Roave/BetterReflection/issues/37
     * @return bool
     */
    public function isClosure()
    {
        return false;
    }

    /**
     * Is this function deprecated?
     *
     * Note - we cannot reflect on internal functions (as there is no PHP source
     * code we can access. This means, at present, we can only EVER return false
     * from this function.
     *
     * @see https://github.com/Roave/BetterReflection/issues/38
     * @return bool
     */
    public function isDeprecated()
    {
        return false;
    }

    /**
     * Is this an internal function?
     *
     * Note - we cannot reflect on internal functions (as there is no PHP source
     * code we can access. This means, at present, we can only EVER return false
     * from this function.
     *
     * @see https://github.com/Roave/BetterReflection/issues/38
     * @return bool
     */
    public function isInternal()
    {
        return false;
    }

    /**
     * Is this a user-defined function (will always return the opposite of
     * whatever isInternal returns).
     *
     * @return bool
     */
    public function isUserDefined()
    {
        return !$this->isInternal();
    }

    /**
     * Check if the function has a variadic parameter
     *
     * @return bool
     */
    public function isVariadic()
    {
        $parameters = $this->getParameters();

        foreach ($parameters as $parameter) {
            if ($parameter->isVariadic()) {
                return true;
            }
        }

        return false;
    }
}
