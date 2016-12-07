<?php

declare(strict_types=1);

namespace BetterReflectionTest\Reflection;

use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Types;
use BetterReflection\Reflection\ReflectionVariable;
use BetterReflection\Reflection\ReflectionType;
use PhpParser\Node\Param;
use PhpParser\Node\Expr\Variable;

/**
 * @covers \BetterReflection\Reflection\ReflectionVariable
 */
class ReflectionVariableTest extends \PHPUnit_Framework_TestCase
{
    private $reflectionType;

    public function setUp()
    {
        $this->reflectionType = $this->prophesize(ReflectionType::class);
    }

    public function testCreateFromParamAndType()
    {
        $this->reflectionType->__toString()->willReturn('string');

        $variable = ReflectionVariable::createFromParamAndType(
            $this->createParam('foobar'),
            $this->reflectionType->reveal()
        );
        $this->assertInstanceOf(ReflectionVariable::class, $variable);
        $this->assertEquals('foobar', $variable->getName());
        $this->assertSame($this->reflectionType->reveal(), $variable->getType());
        $this->assertEquals(10, $variable->getStartPos());
        $this->assertEquals(20, $variable->getEndPos());
    }

    public function testCreateFromVariableAndType()
    {
        $this->reflectionType->__toString()->willReturn('string');

        $variable = ReflectionVariable::createFromVariableAndType(
            $this->createVariable('foobar'),
            $this->reflectionType->reveal()
        );
        $this->assertInstanceOf(ReflectionVariable::class, $variable);
        $this->assertEquals('foobar', $variable->getName());
        $this->assertSame($this->reflectionType->reveal(), $variable->getType());
        $this->assertEquals(10, $variable->getStartPos());
        $this->assertEquals(20, $variable->getEndPos());
    }

    /**
     * NOTE: It should be better to use the Builder here as the constructor
     *       could change with later versions of PHP, however the builder does not
     *       allow the setting of attributes.
     */
    public function createParam($name): Param
    {
        return new Param(
            $name, null, null, false, false, [
                'startFilePos' => 10,
                'endFilePos' => 20,
            ]
        );
    }

    public function createVariable($name): Variable
    {
        return new Variable(
            $name, [
                'startFilePos' => 10,
                'endFilePos' => 20,
            ]
        );
    }
}
