<?php

namespace BetterReflectionTest\TypesFinder;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionFunction;
use BetterReflection\Reflection\ReflectionMethod;
use BetterReflection\SourceLocator\LocatedSource;
use BetterReflection\TypesFinder\FindParameterType;
use PhpParser\Node\Param as ParamNode;
use phpDocumentor\Reflection\Types;

/**
 * @covers \BetterReflection\TypesFinder\FindParameterType
 */
class FindParameterTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function parameterTypeProvider()
    {
        return [
            ['@param int|string $foo', 'foo', [Types\Integer::class, Types\String_::class]],
            ['@param array $foo', 'foo', [Types\Array_::class]],
            ['@param \stdClass $foo', 'foo', [Types\Object_::class]],
            ['@param int|int[]|int[][] $foo', 'foo', [Types\Integer::class, Types\Array_::class, Types\Array_::class]],
            ['', 'foo', []],
        ];
    }

    /**
     * @param string $docBlock
     * @param string $nodeName
     * @param string[] $expectedInstances
     * @dataProvider parameterTypeProvider
     */
    public function testFindParameterTypeForFunction($docBlock, $nodeName, $expectedInstances)
    {
        $node = new ParamNode($nodeName);
        $docBlock = "/**\n * $docBlock\n */";

        $function = $this->getMockBuilder(ReflectionFunction::class)
            ->setMethods(['getDocComment', 'getLocatedSource'])
            ->disableOriginalConstructor()
            ->getMock();

        $function
            ->expects($this->once())
            ->method('getDocComment')
            ->will($this->returnValue($docBlock));

        $function
            ->expects($this->once())
            ->method('getLocatedSource')
            ->will($this->returnValue(new LocatedSource('<?php', null)));

        /* @var ReflectionFunction $function */
        $foundTypes = (new FindParameterType())->__invoke($function, $node);

        $this->assertCount(count($expectedInstances), $foundTypes);

        foreach ($expectedInstances as $i => $expectedInstance) {
            $this->assertInstanceOf($expectedInstance, $foundTypes[$i]);
        }
    }

    /**
     * @param string $docBlock
     * @param string $nodeName
     * @param string[] $expectedInstances
     * @dataProvider parameterTypeProvider
     */
    public function testFindParameterTypeForMethod($docBlock, $nodeName, $expectedInstances)
    {
        $node = new ParamNode($nodeName);
        $docBlock = "/**\n * $docBlock\n */";

        $class = $this->getMockBuilder(ReflectionClass::class)
            ->setMethods(['getLocatedSource'])
            ->disableOriginalConstructor()
            ->getMock();

        $class
            ->expects($this->once())
            ->method('getLocatedSource')
            ->will($this->returnValue(new LocatedSource('<?php', null)));

        $method = $this->getMockBuilder(ReflectionMethod::class)
            ->setMethods(['getDocComment', 'getDeclaringClass'])
            ->disableOriginalConstructor()
            ->getMock();

        $method
            ->expects($this->once())
            ->method('getDocComment')
            ->will($this->returnValue($docBlock));

        $method
            ->expects($this->once())
            ->method('getDeclaringClass')
            ->will($this->returnValue($class));

        /* @var ReflectionMethod $method */
        $foundTypes = (new FindParameterType())->__invoke($method, $node);

        $this->assertCount(count($expectedInstances), $foundTypes);

        foreach ($expectedInstances as $i => $expectedInstance) {
            $this->assertInstanceOf($expectedInstance, $foundTypes[$i]);
        }
    }
}
