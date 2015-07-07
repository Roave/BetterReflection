<?php

namespace BetterReflectionTest\TypesFinder;

use BetterReflection\Reflection\ReflectionProperty;
use BetterReflection\TypesFinder\FindPropertyType;
use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\Comment\Doc as DocNode;
use phpDocumentor\Reflection\Types;

/**
 * @covers \BetterReflection\TypesFinder\FindPropertyType
 */
class FindPropertyTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function propertyTypeProvider()
    {
        return [
            ['@var int|string $foo', 'foo', [Types\Integer::class, Types\String_::class]],
            ['@var array $foo', 'foo', [Types\Array_::class]],
            ['@var \stdClass $foo', 'foo', [Types\Object_::class]],
            ['@var int|int[]|int[][] $foo', 'foo', [Types\Integer::class, Types\Array_::class, Types\Array_::class]],
            ['', 'foo', []],
        ];
    }

    /**
     * @param string $docBlock
     * @param string $nodeName
     * @param string[] $expectedInstances
     * @dataProvider propertyTypeProvider
     */
    public function testFindPropertyType($docBlock, $nodeName, $expectedInstances)
    {
        $node = new PropertyNode(
            $nodeName,
            [],
            [
                'comments' => [
                    new DocNode("/**\n * $docBlock\n */"),
                ],
            ]
        );

        $property = $this->getMockBuilder(ReflectionProperty::class)
            ->setMethods(['getFileName', 'getDeclaringClass', 'getNamespaceName'])
            ->disableOriginalConstructor()
            ->getMock();

        $property->expects($this->any())->method('getFileName')
            ->will($this->returnValue(__DIR__ . '/../Fixture/NoNamespace.php'));

        $property->expects($this->any())->method('getDeclaringClass')
            ->will($this->returnSelf());

        $property->expects($this->any())->method('getNamespaceName')
            ->will($this->returnValue(''));

        /* @var ReflectionProperty $property */
        $foundTypes = (new FindPropertyType())->__invoke($node, $property);

        $this->assertCount(count($expectedInstances), $foundTypes);

        foreach ($expectedInstances as $i => $expectedInstance) {
            $this->assertInstanceOf($expectedInstance, $foundTypes[$i]);
        }
    }

    public function testFindPropertyTypeReturnsEmptyArrayWhenNoCommentsNodesFound()
    {
        $node = new PropertyNode('foo', []);

        $property = $this->getMockBuilder(ReflectionProperty::class)
            ->setMethods(['getFileName', 'getDeclaringClass', 'getNamespaceName'])
            ->disableOriginalConstructor()
            ->getMock();

        $property->expects($this->any())->method('getFileName')
            ->will($this->returnValue(__DIR__ . '/../Fixture/NoNamespace.php'));

        $property->expects($this->any())->method('getDeclaringClass')
            ->will($this->returnSelf());

        $property->expects($this->any())->method('getNamespaceName')
            ->will($this->returnValue(''));

        /* @var ReflectionProperty $property */
        $foundTypes = (new FindPropertyType())->__invoke($node, $property);

        $this->assertSame([], $foundTypes);
    }
}
