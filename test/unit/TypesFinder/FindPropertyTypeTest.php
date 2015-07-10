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
            ['@var int|string $foo', [Types\Integer::class, Types\String_::class]],
            ['@var array $foo', [Types\Array_::class]],
            ['@var \stdClass $foo', [Types\Object_::class]],
            ['@var int|int[]|int[][] $foo', [Types\Integer::class, Types\Array_::class, Types\Array_::class]],
            ['', []],
        ];
    }

    /**
     * @param string $docBlock
     * @param string[] $expectedInstances
     * @dataProvider propertyTypeProvider
     */
    public function testFindPropertyType($docBlock, $expectedInstances)
    {
        $class = $this->getMockBuilder(ReflectionClass::class)
            ->setMethods(['getNamespaceName', 'getLocatedSource'])
            ->disableOriginalConstructor()
            ->getMock();

        $class->expects($this->any())->method('getNamespaceName')
            ->will($this->returnValue(''));

        $class->expects($this->any())->method('getLocatedSource')
            ->will($this->returnValue(new LocatedSource('<?php', null)));

        $property = $this->getMockBuilder(ReflectionProperty::class)
            ->setMethods(['getDeclaringClass', 'getDocComment'])
            ->disableOriginalConstructor()
            ->getMock();

        $property->expects($this->any())->method('getDeclaringClass')
            ->will($this->returnValue($class));

        $property->expects($this->any())->method('getDocComment')
            ->will($this->returnValue("/**\n * $docBlock\n */"));

        /* @var ReflectionProperty $property */
        $foundTypes = (new FindPropertyType())->__invoke($property);

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
        $foundTypes = (new FindPropertyType())->__invoke($property);

        $this->assertSame([], $foundTypes);
    }
}
