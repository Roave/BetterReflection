<?php

namespace Roave\BetterReflectionTest\Reflection;

use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflectionTest\Fixture\ExampleClass;

class ReflectionClassConstantTest extends \PHPUnit_Framework_TestCase
{
    private function getComposerLocator() : ComposerSourceLocator
    {
        return new ComposerSourceLocator(
            require __DIR__ . '/../../../vendor/autoload.php'
        );
    }

    public function testDefaultVisibility()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);
        $const = $classInfo->getReflectionConstant('MY_CONST_1');
        $this->assertTrue($const->isPublic());
    }

    public function testPublicVisibility()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);
        $const = $classInfo->getReflectionConstant('MY_CONST_3');
        $this->assertTrue($const->isPublic());
    }

    public function testProtectedVisibility()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);
        $const = $classInfo->getReflectionConstant('MY_CONST_4');
        $this->assertTrue($const->isProtected());
    }

    public function testPrivateVisibility()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);
        $const = $classInfo->getReflectionConstant('MY_CONST_5');
        $this->assertTrue($const->isPrivate());
    }
}