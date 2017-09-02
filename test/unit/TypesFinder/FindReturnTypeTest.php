<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\TypesFinder;

use phpDocumentor\Reflection\Types;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\TypesFinder\FindReturnType;

/**
 * @covers \Roave\BetterReflection\TypesFinder\FindReturnType
 */
class FindReturnTypeTest extends TestCase
{
    /**
     * @return array
     */
    public function returnTypeProvider() : array
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
    public function testFindReturnTypeForFunction(string $docBlock, array $expectedInstances) : void
    {
        $docBlock = "/**\n * $docBlock\n */";

        /* @var $function ReflectionMethod|\PHPUnit_Framework_MockObject_MockObject */
        $function = $this->createMock(ReflectionFunction::class);

        $function
            ->expects(self::once())
            ->method('getDocComment')
            ->willReturn($docBlock);

        $foundTypes = (new FindReturnType())->__invoke($function, null);

        self::assertCount(\count($expectedInstances), $foundTypes);

        foreach ($expectedInstances as $i => $expectedInstance) {
            self::assertInstanceOf($expectedInstance, $foundTypes[$i]);
        }
    }

    /**
     * @param string $docBlock
     * @param string[] $expectedInstances
     * @dataProvider returnTypeProvider
     */
    public function testFindReturnTypeForMethod(string $docBlock, array $expectedInstances) : void
    {
        $docBlock = "/**\n * $docBlock\n */";

        /* @var $method ReflectionMethod|\PHPUnit_Framework_MockObject_MockObject */
        $method = $this->createMock(ReflectionMethod::class);

        $method
            ->expects($this->once())
            ->method('getDocComment')
            ->will($this->returnValue($docBlock));

        $foundTypes = (new FindReturnType())->__invoke($method, null);

        self::assertCount(\count($expectedInstances), $foundTypes);

        foreach ($expectedInstances as $i => $expectedInstance) {
            self::assertInstanceOf($expectedInstance, $foundTypes[$i]);
        }
    }

    public function testFindReturnTypeForFunctionWithNoDocBlock() : void
    {
        /* @var $function ReflectionFunction|\PHPUnit_Framework_MockObject_MockObject */
        $function = $this->createMock(ReflectionFunction::class);

        $function
            ->expects(self::once())
            ->method('getDocComment')
            ->will(self::returnValue(''));

        self::assertEmpty((new FindReturnType())->__invoke($function, null));
    }
}
