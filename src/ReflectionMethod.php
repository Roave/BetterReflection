<?php

namespace Asgrim;

use PhpParser\Node\Stmt\ClassMethod as MethodNode;

class ReflectionMethod
{
    public $name;

    private $declaringClass;

    private function __construct()
    {
    }

    public static function createFromNode(MethodNode $node, ReflectionClass $declaringClass)
    {
        $method = new self();
        $method->name = $node->name;
        $method->declaringClass = $declaringClass;
        return $method;
    }
}
