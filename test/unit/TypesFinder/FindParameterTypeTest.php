<?php

namespace BetterReflectionTest\TypesFinder;

use BetterReflection\Reflection\ReflectionFunctionAbstract;
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
    public function testFindParameterType($docBlock, $nodeName, $expectedInstances)
    {
        $node = new ParamNode($nodeName);
        $docBlock = "/**\n * $docBlock\n */";

        $function = $this->getMockBuilder(ReflectionFunctionAbstract::class)
            ->setMethods(['getDocComment', 'getLocatedSource'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $function
            ->expects($this->once())
            ->method('getDocComment')
            ->will($this->returnValue($docBlock));

        $function
            ->expects($this->once())
            ->method('getLocatedSource')
            ->will($this->returnValue(new LocatedSource('<?php', null)));

        /* @var ReflectionFunctionAbstract $function */
        $foundTypes = (new FindParameterType())->__invoke($function, $node);

        $this->assertCount(count($expectedInstances), $foundTypes);

        foreach ($expectedInstances as $i => $expectedInstance) {
            $this->assertInstanceOf($expectedInstance, $foundTypes[$i]);
        }
    }
}
