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

    /**
     * @param string $const
     * @param string $expected
     * @dataProvider toStringProvider
     */
    public function testToString(string $const, string $expected)
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);
        $const = $classInfo->getReflectionConstant($const);
        $this->assertEquals($expected, (string)$const);
    }

    public function toStringProvider()
    {
        return [
            ['MY_CONST_1', 'Constant [ public MY_CONST_1 ] { 123 }' . PHP_EOL],
            ['MY_CONST_3', 'Constant [ public MY_CONST_3 ] { 345 }' . PHP_EOL],
            ['MY_CONST_4', 'Constant [ protected MY_CONST_4 ] { 456 }' . PHP_EOL],
            ['MY_CONST_5', 'Constant [ private MY_CONST_5 ] { 567 }' . PHP_EOL],
        ];
    }
}