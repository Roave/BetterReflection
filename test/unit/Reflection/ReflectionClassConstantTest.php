<?php

namespace Roave\BetterReflectionTest\Reflection;

use PhpParser\Node\Stmt\Class_;
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

    private function getExampleConstant(string $name)
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);
        return $classInfo->getReflectionConstant($name);
    }

    public function testDefaultVisibility()
    {
        $const = $this->getExampleConstant('MY_CONST_1');
        $this->assertTrue($const->isPublic());
    }

    public function testPublicVisibility()
    {
        $const = $this->getExampleConstant('MY_CONST_3');
        $this->assertTrue($const->isPublic());
    }

    public function testProtectedVisibility()
    {
        $const = $this->getExampleConstant('MY_CONST_4');
        $this->assertTrue($const->isProtected());
    }

    public function testPrivateVisibility()
    {
        $const = $this->getExampleConstant('MY_CONST_5');
        $this->assertTrue($const->isPrivate());
    }

    /**
     * @param string $const
     * @param string $expected
     * @dataProvider toStringProvider
     */
    public function testToString(string $const, string $expected)
    {
        $const = $this->getExampleConstant($const);
        $this->assertSame($expected, (string)$const);
    }

    public function toStringProvider()
    {
        return [
            ['MY_CONST_1', 'Constant [ public integer MY_CONST_1 ] { 123 }' . PHP_EOL],
            ['MY_CONST_3', 'Constant [ public integer MY_CONST_3 ] { 345 }' . PHP_EOL],
            ['MY_CONST_4', 'Constant [ protected integer MY_CONST_4 ] { 456 }' . PHP_EOL],
            ['MY_CONST_5', 'Constant [ private integer MY_CONST_5 ] { 567 }' . PHP_EOL],
        ];
    }

    /**
     * @param string $const
     * @param int $expected
     * @dataProvider getModifiersProvider
     */
    public function testGetModifiers(string $const, int $expected)
    {
        $const = $this->getExampleConstant($const);
        $this->assertSame($expected, $const->getModifiers());
    }

    public function getModifiersProvider()
    {
        return [
            ['MY_CONST_1', \ReflectionProperty::IS_PUBLIC],
            ['MY_CONST_3', \ReflectionProperty::IS_PUBLIC],
            ['MY_CONST_4', \ReflectionProperty::IS_PROTECTED],
            ['MY_CONST_5', \ReflectionProperty::IS_PRIVATE],
        ];
    }

    public function testGetDocComment()
    {
        $const = $this->getExampleConstant('MY_CONST_2');
        $this->assertContains('Documentation for constant', $const->getDocComment());
    }

    public function testGetDocCommentReturnsEmptyStringWithNoComment()
    {
        $const = $this->getExampleConstant('MY_CONST_1');
        $this->assertSame('', $const->getDocComment());
    }

    public function testGetDeclaringClass()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);
        $const = $classInfo->getReflectionConstant('MY_CONST_1');
        $this->assertSame($classInfo, $const->getDeclaringClass());
    }

}