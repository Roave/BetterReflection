<?php
namespace Asgrim;

use Composer\Autoload\ClassLoader;

class Investigator
{
    private $classLoader;

    public function __construct(ClassLoader $classLoader)
    {
        $this->classLoader = $classLoader;
    }

    public function investigate($className)
    {
        return new ClassInfo();
    }
}
