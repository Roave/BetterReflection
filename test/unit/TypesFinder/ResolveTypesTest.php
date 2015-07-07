<?php

namespace BetterReflectionTest\TypesFinder;

use BetterReflection\TypesFinder\ResolveTypes;
use phpDocumentor\Reflection\Types;
use phpDocumentor\Reflection\Types\Context;

/**
 * @covers \BetterReflection\TypesFinder\ResolveTypes
 */
class ResolveTypesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function basicTypesToResolveProvider()
    {
        $context = new Context(
            'My\Space',
            [
                'Foo\Bar',
                'Bat\Baz',
            ]
        );

        return [
            [['array'], [Types\Array_::class]],
            [['int[]'], [Types\Array_::class]],
            [['string'], [Types\String_::class]],
            [['int'], [Types\Integer::class]],
            [['integer'], [Types\Integer::class]],
            [['bool'], [Types\Boolean::class]],
            [['boolean'], [Types\Boolean::class]],
            [['int', 'string', 'bool'], [Types\Integer::class, Types\String_::class, Types\Boolean::class]],
        ];
    }

    /**
     * @param string[] $inputTypes
     * @param $expectedInstances
     * @dataProvider basicTypesToResolveProvider
     */
    public function testResolveTypesWithBasicTypes($inputTypes, $expectedInstances)
    {
        $resolvedTypes = (new ResolveTypes())->__invoke($inputTypes, new Context(''));

        $this->assertCount(count($expectedInstances), $resolvedTypes);

        foreach ($expectedInstances as $i => $expectedInstance) {
            $this->assertInstanceOf($expectedInstance, $resolvedTypes[$i]);
        }
    }

    /**
     * @return array
     */
    public function contextualTypesToResolveProvider()
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
    public function testResolveTypesWithContextualTypes($inputType, $expectedType)
    {
        $context = new Context(
            'Foo\Awesome',
            [
                'Bar' => 'Foo\Bar',
                'Baz' => 'Bat\Baz',
            ]
        );

        $resolvedTypes = (new ResolveTypes())->__invoke([$inputType], $context);
        $this->assertCount(1, $resolvedTypes);

        $resolvedType = reset($resolvedTypes);
        $this->assertInstanceOf(Types\Object_::class, $resolvedType);

        /* @var $resolvedType Types\Object_ */
        $this->assertSame($expectedType, (string)$resolvedType->getFqsen());
    }

}
