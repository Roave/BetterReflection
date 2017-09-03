<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\TypesFinder;

use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types;
use PhpParser\Builder\Use_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Namespace_;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;
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

    /**
     * @dataProvider aliasedReturnTypesProvider
     *
     * @param string|null $namespaceName
     * @param string[]    $aliasesToFQCNs indexed by alias
     * @param string      $returnType
     * @param Type[]      $expectedTypes
     */
    public function testWillResolveAliasedTypes(
        ?string $namespaceName,
        array $aliasesToFQCNs,
        string $returnType,
        array $expectedTypes
    ) : void {
        $docBlock = "/**\n * @return $returnType\n */";

        /* @var $function ReflectionFunctionAbstract|\PHPUnit_Framework_MockObject_MockObject */
        $function = $this->createMock(ReflectionFunctionAbstract::class);

        $function
            ->expects($this->once())
            ->method('getDocComment')
            ->will($this->returnValue($docBlock));

        $uses = [];

        foreach ($aliasesToFQCNs as $alias => $fqcn) {
            $uses[] = (new Use_($fqcn, Stmt\Use_::TYPE_NORMAL))
                ->as($alias)
                ->getNode();
        }

        $namespace = new Namespace_($namespaceName ? new Name($namespaceName) : null, $uses);

        self::assertEquals($expectedTypes, (new FindReturnType())->__invoke($function, $namespace));
    }

    public function aliasedReturnTypesProvider() : array
    {
        return [
            'No namespace' => [
                null,
                [
                    'Bar' => 'Bar',
                    'Baz' => 'Taw\\Taz',
                ],
                'Foo|Bar|Baz|Tab',
                [
                    new Types\Object_(new Fqsen('\\Foo')),
                    new Types\Object_(new Fqsen('\\Bar')),
                    new Types\Object_(new Fqsen('\\Taw\\Taz')),
                    new Types\Object_(new Fqsen('\\Tab')),
                ],
            ],
            'Foo' => [
                'Foo',
                [
                    'Bar' => 'Bar',
                    'Baz' => 'Taw\\Taz',
                ],
                'Foo|Bar|Baz|Tab',
                [
                    new Types\Object_(new Fqsen('\\Foo\\Foo')),
                    new Types\Object_(new Fqsen('\\Bar')),
                    new Types\Object_(new Fqsen('\\Taw\\Taz')),
                    new Types\Object_(new Fqsen('\\Foo\\Tab')),
                ],
            ],
        ];
    }
}
