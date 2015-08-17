<?php

namespace BetterReflection\Reflection\Adapter;

use ReflectionFunction as CoreReflectionFunction;
use BetterReflection\Reflection\ReflectionFunction as BetterReflectionFunction;

class ReflectionFunction extends CoreReflectionFunction
{
    /**
     * @var BetterReflectionFunction
     */
    private $betterReflectionFunction;

    public function __construct(BetterReflectionFunction $betterReflectionFunction)
    {
        $this->betterReflectionFunction = $betterReflectionFunction;
    }

    /**
     * @return string
     */
    public static function export($name, $return = null)
    {
        return BetterReflectionFunction::export(...func_get_args());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->betterReflectionFunction->__toString();
    }

    /**
     * @return bool
     */
    public function inNamespace()
    {
        return $this->betterReflectionFunction->inNamespace();
    }

    /**
     * @return bool
     */
    public function isClosure()
    {
        return $this->betterReflectionFunction->isClosure();
    }

    /**
     * @return bool
     */
    public function isDeprecated()
    {
        return $this->betterReflectionFunction->isDeprecated();
    }

    /**
     * @return bool
     */
    public function isInternal()
    {
        return $this->betterReflectionFunction->isInternal();
    }

    /**
     * @return bool
     */
    public function isUserDefined()
    {
        return $this->betterReflectionFunction->isUserDefined();
    }

    /**
     * @throws \Exception
     */
    public function getClosureThis()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @throws \Exception
     */
    public function getClosureScopeClass()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @return string
     */
    public function getDocComment()
    {
        return $this->betterReflectionFunction->getDocComment();
    }

    /**
     * @return int
     */
    public function getEndLine()
    {
        return $this->betterReflectionFunction->getEndLine();
    }

    /**
     * @throws \Exception
     */
    public function getExtension()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @throws \Exception
     */
    public function getExtensionName()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->betterReflectionFunction->getFileName();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->betterReflectionFunction->getName();
    }

    /**
     * @return string
     */
    public function getNamespaceName()
    {
        return $this->betterReflectionFunction->getNamespaceName();
    }

    /**
     * @return int
     */
    public function getNumberOfParameters()
    {
        return $this->betterReflectionFunction->getNumberOfParameters();
    }

    /**
     * @return int
     */
    public function getNumberOfRequiredParameters()
    {
        return $this->betterReflectionFunction->getNumberOfRequiredParameters();
    }

    /**
     * @return ReflectionParameter[]
     */
    public function getParameters()
    {
        $parameters = $this->betterReflectionFunction->getParameters();

        $wrappedParameters = [];
        foreach ($parameters as $key => $parameter) {
            $wrappedParameters[$key] = new ReflectionParameter($parameter);
        }
        return $wrappedParameters;
    }

    /**
     * @return string
     */
    public function getShortName()
    {
        return $this->betterReflectionFunction->getShortName();
    }

    /**
     * @return int
     */
    public function getStartLine()
    {
        return $this->betterReflectionFunction->getStartLine();
    }

    /**
     * @throws \Exception
     */
    public function getStaticVariables()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @return bool
     */
    public function returnsReference()
    {
        return $this->betterReflectionFunction->returnsReference();
    }

    /**
     * @return bool
     */
    public function isGenerator()
    {
        return $this->betterReflectionFunction->isGenerator();
    }

    /**
     * @return bool
     */
    public function isVariadic()
    {
        return $this->betterReflectionFunction->isVariadic();
    }

    /**
     * @throws \Exception
     */
    public function isDisabled()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @throws \Exception
     */
    public function invoke($args = null)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @throws \Exception
     */
    public function invokeArgs(array $args)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @throws \Exception
     */
    public function getClosure()
    {
        throw new \Exception('Not implemented');
    }
}
