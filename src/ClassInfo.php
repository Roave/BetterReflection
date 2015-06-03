<?php
namespace Asgrim;

class ClassInfo
{
    public function getMethods()
    {
        return [new MethodInfo()];
    }

    public function hasMethod($methodName)
    {
        return true;
    }
}
