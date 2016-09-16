<?php

namespace BetterReflectionTest\TypesFinder;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionFunction;
use BetterReflection\Reflection\ReflectionMethod;
use BetterReflection\SourceLocator\Located\LocatedSource;
use BetterReflection\TypesFinder\FindReturnType;
use phpDocumentor\Reflection\Types;

/**
 * @covers \BetterReflection\TypesFinder\FindReturnType
 */
class FindReturnTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function returnTypeProvider()
    {
        return [
            ['@return int|string', [Types\Integer::class, Types\String_::class]],
            ['@return array', [Types\Array_::class]],
            ['@return \stdClass', [Types\Object_::class]],
            ['@return int|int[]|int[][]', [Types\Integer::class, Types\Array_::class, Types\Array_::class]],
            ['@return int A comment about the return type', [Types\Integer::class]],
            ['', []],
        ];
    }

    /**
     * @param string $docBlock
     * @param string[] $expectedInstances
     * @dataProvider returnTypeProvider
     */
    public function testFindReturnTypeForFunction($docBlock, $expectedInstances)
    {
        $docBlock = "/**\n * $docBlock\n */";

        $function = $this->createMock(ReflectionFunction::class);

        $function
            ->expects($this->once())
            ->method('getDocComment')
            ->will($this->returnValue($docBlock));

        $function
            ->expects($this->once())
            ->method('getLocatedSource')
            ->will($this->returnValue(new LocatedSource('<?php', null)));

        /* @var ReflectionFunction $function */
        $foundTypes = (new FindReturnType())->__invoke($function);

        $this->assertCount(count($expectedInstances), $foundTypes);

        foreach ($expectedInstances as $i => $expectedInstance) {
            $this->assertInstanceOf($expectedInstance, $foundTypes[$i]);
        }
    }

    /**
     * @param string $docBlock
     * @param string[] $expectedInstances
     * @dataProvider returnTypeProvider
     */
    public function testFindReturnTypeForMethod($docBlock, $expectedInstances)
    {
        $docBlock = "/**\n * $docBlock\n */";

        $class = $this->createMock(ReflectionClass::class);

        $class
            ->expects($this->once())
            ->method('getLocatedSource')
            ->will($this->returnValue(new LocatedSource('<?php', null)));

        $method = $this->createMock(ReflectionMethod::class);

        $method
            ->expects($this->once())
            ->method('getDocComment')
            ->will($this->returnValue($docBlock));

        $method
            ->expects($this->once())
            ->method('getDeclaringClass')
            ->will($this->returnValue($class));

        /* @var ReflectionMethod $method */
        $foundTypes = (new FindReturnType())->__invoke($method);

        $this->assertCount(count($expectedInstances), $foundTypes);

        foreach ($expectedInstances as $i => $expectedInstance) {
            $this->assertInstanceOf($expectedInstance, $foundTypes[$i]);
        }
    }
}
