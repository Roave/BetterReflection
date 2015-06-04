<?php

namespace Asgrim;

abstract class ReflectionFunctionAbstract
{
    protected $name;

    protected function __construct()
    {
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
}
