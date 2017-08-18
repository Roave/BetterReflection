<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\TypesFinder;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflection\TypesFinder\FindPropertyType;
use phpDocumentor\Reflection\Types;

/**
 * @covers \Roave\BetterReflection\TypesFinder\FindPropertyType
 */
class FindPropertyTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function propertyTypeProvider() : array
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
    public function testFindPropertyType(string $docBlock, array $expectedInstances) : void
    {
        $class = $this->createMock(ReflectionClass::class);

        $class->expects($this->any())->method('getNamespaceName')
            ->will($this->returnValue(''));

        $class->expects($this->any())->method('getLocatedSource')
            ->will($this->returnValue(new LocatedSource('<?php', null)));

        $property = $this->createMock(ReflectionProperty::class);

        $property->expects($this->any())->method('getDeclaringClass')
            ->will($this->returnValue($class));

        $property->expects($this->any())->method('getDocComment')
            ->will($this->returnValue("/**\n * $docBlock\n */"));

        /* @var ReflectionProperty $property */
        $foundTypes = (new FindPropertyType())->__invoke($property);

        self::assertCount(count($expectedInstances), $foundTypes);

        foreach ($expectedInstances as $i => $expectedInstance) {
            self::assertInstanceOf($expectedInstance, $foundTypes[$i]);
        }
    }

    public function testNamespaceResolutionForProperty() : void
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

        self::assertSame(['\Psr\Log\LoggerInterface'], $prop->getDocBlockTypeStrings());
    }

    public function testFindPropertyTypeReturnsEmptyArrayWhenNoCommentsNodesFound() : void
    {
        $class = $this->createMock(ReflectionClass::class);

        $class->expects($this->any())->method('getNamespaceName')
            ->will($this->returnValue(''));

        $class->expects($this->any())->method('getLocatedSource')
            ->will($this->returnValue(new LocatedSource('<?php', null)));

        $property = $this->createMock(ReflectionProperty::class);

        $property->expects($this->any())->method('getDeclaringClass')
            ->will($this->returnValue($class));

        $property->expects($this->any())->method('getDocComment')
            ->will($this->returnValue('Nothing here...'));

        /* @var ReflectionProperty $property */
        $foundTypes = (new FindPropertyType())->__invoke($property);

        self::assertSame([], $foundTypes);
    }

    public function testFindPropertyTypeReturnsEmptyArrayWhenNoDocBlockIsPresent() : void
    {
        $property = $this->createMock(ReflectionProperty::class);

        $property->expects(self::once())->method('getDocComment')
            ->will(self::returnValue(''));

        $foundTypes = (new FindPropertyType())->__invoke($property);

        self::assertEmpty($foundTypes);
    }
}
