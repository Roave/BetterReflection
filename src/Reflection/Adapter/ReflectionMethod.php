<?php

namespace BetterReflection\Reflection\Adapter;

use ReflectionMethod as CoreReflectionMethod;
use BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;

class ReflectionMethod extends CoreReflectionMethod
{
    /**
     * @var BetterReflectionMethod
     */
    private $betterReflectionMethod;

    public function __construct(BetterReflectionMethod $betterReflectionMethod)
    {
        $this->betterReflectionMethod = $betterReflectionMethod;
    }

    /**
     * @return string
     */
    public static function export($class, $name, $return = null)
    {
        return BetterReflectionMethod::export(...func_get_args());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->betterReflectionMethod->__toString();
    }

    /**
     * @return bool
     */
    public function inNamespace()
    {
        return $this->betterReflectionMethod->inNamespace();
    }

    /**
     * @return bool
     */
    public function isClosure()
    {
        return $this->betterReflectionMethod->isClosure();
    }

    /**
     * @return bool
     */
    public function isDeprecated()
    {
        return $this->betterReflectionMethod->isDeprecated();
    }

    /**
     * @return bool
     */
    public function isInternal()
    {
        return $this->betterReflectionMethod->isInternal();
    }

    /**
     * @return bool
     */
    public function isUserDefined()
    {
        return $this->betterReflectionMethod->isUserDefined();
    }

    /**
     * @throws \Exception
     */
    public function getClosureThis()
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @throws \Exception
     */
    public function getClosureScopeClass()
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @return string
     */
    public function getDocComment()
    {
        return $this->betterReflectionMethod->getDocComment();
    }

    /**
     * @return int
     */
    public function getEndLine()
    {
        return $this->betterReflectionMethod->getEndLine();
    }

    /**
     * @throws \Exception
     */
    public function getExtension()
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @throws \Exception
     */
    public function getExtensionName()
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->betterReflectionMethod->getFileName();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->betterReflectionMethod->getName();
    }

    /**
     * @return string
     */
    public function getNamespaceName()
    {
        return $this->betterReflectionMethod->getNamespaceName();
    }

    /**
     * @return int
     */
    public function getNumberOfParameters()
    {
        return $this->betterReflectionMethod->getNumberOfParameters();
    }

    /**
     * @return int
     */
    public function getNumberOfRequiredParameters()
    {
        return $this->betterReflectionMethod->getNumberOfRequiredParameters();
    }

    /**
     * @return ReflectionParameter[]
     */
    public function getParameters()
    {
        $parameters = $this->betterReflectionMethod->getParameters();

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
        return $this->betterReflectionMethod->getShortName();
    }

    /**
     * @return int
     */
    public function getStartLine()
    {
        return $this->betterReflectionMethod->getStartLine();
    }

    /**
     * @throws \Exception
     */
    public function getStaticVariables()
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @return bool
     */
    public function returnsReference()
    {
        return $this->betterReflectionMethod->returnsReference();
    }

    /**
     * @return bool
     */
    public function isGenerator()
    {
        return $this->betterReflectionMethod->isGenerator();
    }

    /**
     * @return bool
     */
    public function isVariadic()
    {
        return $this->betterReflectionMethod->isVariadic();
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return $this->betterReflectionMethod->isPublic();
    }

    /**
     * @return bool
     */
    public function isPrivate()
    {
        return $this->betterReflectionMethod->isPrivate();
    }

    /**
     * @return bool
     */
    public function isProtected()
    {
        return $this->betterReflectionMethod->isProtected();
    }

    /**
     * @return bool
     */
    public function isAbstract()
    {
        return $this->betterReflectionMethod->isAbstract();
    }

    /**
     * @return bool
     */
    public function isFinal()
    {
        return $this->betterReflectionMethod->isFinal();
    }

    /**
     * @return bool
     */
    public function isStatic()
    {
        return $this->betterReflectionMethod->isStatic();
    }

    /**
     * @return bool
     */
    public function isConstructor()
    {
        return $this->betterReflectionMethod->isConstructor();
    }

    /**
     * @return bool
     */
    public function isDestructor()
    {
        return $this->betterReflectionMethod->isDestructor();
    }

    /**
     * @throws \Exception
     */
    public function getClosure($object)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @return int
     */
    public function getModifiers()
    {
        return $this->betterReflectionMethod->getModifiers();
    }

    /**
     * @throws \Exception
     */
    public function invoke($object, $args)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @throws \Exception
     */
    public function invokeArgs($object, array $args)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @return ReflectionClass
     */
    public function getDeclaringClass()
    {
        return new ReflectionClass($this->betterReflectionMethod->getDeclaringClass());
    }

    /**
     * @return BetterReflectionMethod
     * @throws \BetterReflection\Reflection\Exception\MethodPrototypeNotFound
     */
    public function getPrototype()
    {
        return new ReflectionMethod($this->betterReflectionMethod->getPrototype());
    }

    /**
     * @throws \Exception
     */
    public function setAccessible($value)
    {
        throw new Exception\NotImplemented('Not implemented');
    }
}
