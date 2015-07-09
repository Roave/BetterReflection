<?php

namespace BetterReflectionTest\TypesFinder;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionProperty;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\LocatedSource;
use BetterReflection\SourceLocator\StringSourceLocator;
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

        $class = $this->getMockBuilder(ReflectionClass::class)
            ->setMethods(['getNamespaceName', 'getLocatedSource'])
            ->disableOriginalConstructor()
            ->getMock();

        $class->expects($this->any())->method('getNamespaceName')
            ->will($this->returnValue(''));

        $class->expects($this->any())->method('getLocatedSource')
            ->will($this->returnValue(new LocatedSource('<?php', null)));

        $property = $this->getMockBuilder(ReflectionProperty::class)
            ->setMethods(['getDeclaringClass'])
            ->disableOriginalConstructor()
            ->getMock();

        $property->expects($this->any())->method('getDeclaringClass')
            ->will($this->returnValue($class));

        /* @var ReflectionProperty $property */
        $foundTypes = (new FindPropertyType())->__invoke($node, $property);

        $this->assertCount(count($expectedInstances), $foundTypes);

        foreach ($expectedInstances as $i => $expectedInstance) {
            $this->assertInstanceOf($expectedInstance, $foundTypes[$i]);
        }
    }

    public function testNamespaceResolutionForProperty()
    {
        $php = '<?php
            namespace MyNamespace;

            use Psr\Log\LoggerInterface;

            class ThingThatLogs
            {
                /**
                 * @var LoggerInterface
                 */
                private $logger;
            }
        ';

        $prop = (new ClassReflector(new StringSourceLocator($php)))
            ->reflect('MyNamespace\ThingThatLogs')
            ->getProperty('logger');

        $this->assertSame(['\Psr\Log\LoggerInterface'], $prop->getDocBlockTypeStrings());
    }

    public function testFindPropertyTypeReturnsEmptyArrayWhenNoCommentsNodesFound()
    {
        $node = new PropertyNode('foo', []);

        $class = $this->getMockBuilder(ReflectionClass::class)
            ->setMethods(['getNamespaceName', 'getLocatedSource'])
            ->disableOriginalConstructor()
            ->getMock();

        $class->expects($this->any())->method('getNamespaceName')
            ->will($this->returnValue(''));

        $class->expects($this->any())->method('getLocatedSource')
            ->will($this->returnValue(new LocatedSource('<?php', null)));

        $property = $this->getMockBuilder(ReflectionProperty::class)
            ->setMethods(['getDeclaringClass'])
            ->disableOriginalConstructor()
            ->getMock();

        $property->expects($this->any())->method('getDeclaringClass')
            ->will($this->returnValue($class));

        /* @var ReflectionProperty $property */
        $foundTypes = (new FindPropertyType())->__invoke($node, $property);

        $this->assertSame([], $foundTypes);
    }
}
