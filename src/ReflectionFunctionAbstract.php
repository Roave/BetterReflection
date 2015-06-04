<?php

namespace Asgrim;

abstract class ReflectionFunctionAbstract
{
    protected $name;

    protected function __construct()
    {
    }

    public function getName()
    {
        return $this->name;
    }
}