<?php

namespace Roave\BetterReflectionTest\TypesFinder;

use Roave\BetterReflection\TypesFinder\ResolveTypes;
use phpDocumentor\Reflection\Types;
use phpDocumentor\Reflection\Types\Context;

/**
 * @covers \Roave\BetterReflection\TypesFinder\ResolveTypes
 */
class ResolveTypesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function basicTypesToResolveProvider() : array
    {
        return [
            [['array'], [Types\Array_::class]],
            [['int[]'], [Types\Array_::class]],
            [['string'], [Types\String_::class]],
            [['int'], [Types\Integer::class]],
            [['integer'], [Types\Integer::class]],
            [['bool'], [Types\Boolean::class]],
            [['boolean'], [Types\Boolean::class]],
            [['int', 'string', 'bool'], [Types\Integer::class, Types\String_::class, Types\Boolean::class]],
            [['?string'], [Types\Nullable::class]],
            [['iterable'], [Types\Iterable_::class]],
        ];
    }

    /**
     * @param string[] $inputTypes
     * @param string[] $expectedInstances
     * @dataProvider basicTypesToResolveProvider
     */
    public function testResolveTypesWithBasicTypes(array $inputTypes, array $expectedInstances) : void
    {
        $resolvedTypes = (new ResolveTypes())->__invoke($inputTypes, new Context(''));

        self::assertCount(count($expectedInstances), $resolvedTypes);

        foreach ($expectedInstances as $i => $expectedInstance) {
            self::assertInstanceOf($expectedInstance, $resolvedTypes[$i]);
        }
    }

    /**
     * @return array
     */
    public function contextualTypesToResolveProvider() : array
    {
        return [
            ['Zap', '\Foo\Awesome\Zap'],
            ['Bar', '\Foo\Bar'],
            ['Baz\Zoom', '\Bat\Baz\Zoom'],
        ];
    }

    /**
     * @param string $inputType
     * @param string $expectedType
     * @dataProvider contextualTypesToResolveProvider
     */
    public function testResolveTypesWithContextualTypes(string $inputType, string $expectedType) : void
    {
        $context = new Context(
            'Foo\Awesome',
            [
                'Bar' => 'Foo\Bar',
                'Baz' => 'Bat\Baz',
            ]
        );

        $resolvedTypes = (new ResolveTypes())->__invoke([$inputType], $context);
        self::assertCount(1, $resolvedTypes);

        $resolvedType = reset($resolvedTypes);
        self::assertInstanceOf(Types\Object_::class, $resolvedType);

        /* @var $resolvedType Types\Object_ */
        self::assertSame($expectedType, (string)$resolvedType->getFqsen());
    }
}
