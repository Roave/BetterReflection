<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\TypesFinder;

use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types;
use PhpParser\Builder\Use_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Param as ParamNode;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_ as UseStatement;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflection\TypesFinder\FindParameterType;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

use function count;
use function sprintf;

/**
 * @covers \Roave\BetterReflection\TypesFinder\FindParameterType
 */
class FindParameterTypeTest extends TestCase
{
    /**
     * @return array
     */
    public function parameterTypeProvider(): array
    {
        return [
            ['@param int|string $foo', 'foo', [Types\Integer::class, Types\String_::class]],
            ['@param array $foo', 'foo', [Types\Array_::class]],
            ['@param \stdClass $foo', 'foo', [Types\Object_::class]],
            ['@param array{foo: string|int} $foo', 'foo', []],
            ['@param int|int[]|int[][] $foo', 'foo', [Types\Integer::class, Types\Array_::class, Types\Array_::class]],
            ['', 'foo', []],
            ['@param ?string $foo', 'foo', [Types\Nullable::class]],
            ['@param iterable $foo', 'foo', [Types\Iterable_::class]],
            ['@param ?iterable $foo', 'foo', [Types\Nullable::class]],
            ['@param object $foo', 'foo', [Types\Object_::class]],
            ['@param ?object $foo', 'foo', [Types\Nullable::class]],
        ];
    }

    public function testNamespaceResolutionForProperty(): void
    {
        $php = '<?php
            namespace MyNamespace;

            use Psr\Log\LoggerInterface;

            class ThingThatLogs
            {
                /**
                 * @param LoggerInterface $bar
                 */
                public function foo($bar) {}
            }
        ';

        $param = (new ClassReflector(new StringSourceLocator($php, BetterReflectionSingleton::instance()->astLocator())))
            ->reflect('MyNamespace\ThingThatLogs')
            ->getMethod('foo')
            ->getParameter('bar');

        self::assertSame(['\Psr\Log\LoggerInterface'], $param->getDocBlockTypeStrings());
    }

    /**
     * @param string[] $expectedInstances
     *
     * @dataProvider parameterTypeProvider
     */
    public function testFindParameterTypeForFunction(string $docBlock, string $nodeName, array $expectedInstances): void
    {
        $node     = new ParamNode(new Variable($nodeName));
        $docBlock = sprintf("/**\n * %s\n */", $docBlock);

        $function = $this->createMock(ReflectionFunction::class);

        $function
            ->expects($this->once())
            ->method('getDocComment')
            ->will($this->returnValue($docBlock));

        $foundTypes = (new FindParameterType())->__invoke($function, null, $node);

        self::assertCount(count($expectedInstances), $foundTypes);

        foreach ($expectedInstances as $i => $expectedInstance) {
            self::assertInstanceOf($expectedInstance, $foundTypes[$i]);
        }
    }

    /**
     * @param string[] $expectedInstances
     *
     * @dataProvider parameterTypeProvider
     */
    public function testFindParameterTypeForMethod(string $docBlock, string $nodeName, array $expectedInstances): void
    {
        $node     = new ParamNode(new Variable($nodeName));
        $docBlock = sprintf("/**\n * %s\n */", $docBlock);

        $method = $this->createMock(ReflectionFunctionAbstract::class);

        $method
            ->expects($this->once())
            ->method('getDocComment')
            ->will($this->returnValue($docBlock));

        $foundTypes = (new FindParameterType())->__invoke($method, null, $node);

        self::assertCount(count($expectedInstances), $foundTypes);

        foreach ($expectedInstances as $i => $expectedInstance) {
            self::assertInstanceOf($expectedInstance, $foundTypes[$i]);
        }
    }

    public function testFindParameterTypeForFunctionWithNoDocBlock(): void
    {
        $node = new ParamNode(new Variable('foo'));

        $function = $this->createMock(ReflectionFunctionAbstract::class);

        $function
            ->expects(self::once())
            ->method('getDocComment')
            ->will(self::returnValue(''));

        self::assertEmpty((new FindParameterType())->__invoke($function, null, $node));
    }

    /**
     * @param string[] $aliasesToFQCNs indexed by alias
     * @param Type[]   $expectedTypes
     *
     * @dataProvider aliasedParameterTypesProvider
     */
    public function testWillResolveAliasedTypes(
        ?string $namespaceName,
        array $aliasesToFQCNs,
        string $docBlockType,
        array $expectedTypes
    ): void {
        $docBlock = sprintf("/**\n * @param %s \$foo\n */", $docBlockType);

        $parameterNode = new ParamNode(new Variable('foo'));

        $function = $this->createMock(ReflectionFunctionAbstract::class);

        $function
            ->expects(self::once())
            ->method('getDocComment')
            ->willReturn($docBlock);

        $uses = [];

        foreach ($aliasesToFQCNs as $alias => $fqcn) {
            $uses[] = (new Use_($fqcn, UseStatement::TYPE_NORMAL))
                ->as($alias)
                ->getNode();
        }

        $namespace = new Namespace_($namespaceName ? new Name($namespaceName) : null, $uses);

        self::assertEquals($expectedTypes, (new FindParameterType())->__invoke($function, $namespace, $parameterNode));
    }

    public function aliasedParameterTypesProvider(): array
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
