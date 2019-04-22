<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Psr;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Exception\InvalidPrefixMapping;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr0Mapping;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr0Mapping
 */
class Psr0MappingTest extends TestCase
{
    /**
     * @dataProvider mappings
     *
     * @param array[]  $mappings
     * @param string[] $expectedDirectories
     */
    public function testExpectedDirectories(array $mappings, array $expectedDirectories) : void
    {
        self::assertEquals($expectedDirectories, Psr0Mapping::fromArrayMappings($mappings)->directories());
    }

    /**
     * @dataProvider mappings
     *
     * @param array[] $mappings
     */
    public function testIdempotentConstructor(array $mappings) : void
    {
        self::assertEquals(Psr0Mapping::fromArrayMappings($mappings), Psr0Mapping::fromArrayMappings($mappings));
    }

    /** @return array<string, array<int, array<string, array<int, string>>|array<int, string>>> */
    public function mappings() : array
    {
        return [
            'one directory, one prefix'                  => [
                ['foo' => [__DIR__]],
                [__DIR__],
            ],
            'two directories, one prefix'                => [
                ['foo' => [__DIR__, __DIR__ . '/../..']],
                [__DIR__, __DIR__ . '/../..'],
            ],
            'two directories, one duplicate, one prefix' => [
                ['foo' => [__DIR__, __DIR__, __DIR__ . '/../..']],
                [__DIR__, __DIR__ . '/../..'],
            ],
            'two directories, two prefixes'              => [
                [
                    'foo' => [__DIR__],
                    'bar' => [__DIR__ . '/../..'],
                ],
                [__DIR__, __DIR__ . '/../..'],
            ],
            'trailing slash in directory is trimmed' => [
                ['foo' => [__DIR__ . '/']],
                [__DIR__],
            ],
        ];
    }

    /**
     * @dataProvider classLookupMappings
     *
     * @param array[]  $mappings
     * @param string[] $expectedFiles
     */
    public function testClassLookups(array $mappings, Identifier $identifier, array $expectedFiles) : void
    {
        self::assertEquals(
            $expectedFiles,
            Psr0Mapping::fromArrayMappings($mappings)->resolvePossibleFilePaths($identifier)
        );
    }

    /** @return array<string, array<int, array<string, array<int, string>>|array<int, string>>> */
    public function classLookupMappings() : array
    {
        return [
            'empty mappings, no match'                  => [
                [],
                new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [],
            ],
            'one mapping, no match for function identifier'                        => [
                ['Foo\\' => [__DIR__]],
                new Identifier('Foo\\Bar', new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)),
                [],
            ],
            'one mapping, match'                        => [
                ['Foo\\' => [__DIR__]],
                new Identifier('Foo\\Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/Foo/Bar.php'],
            ],
            'one mapping, no match with underscore prefix and namespaced class'                        => [
                ['Foo_' => [__DIR__]],
                new Identifier('Foo\\Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [],
            ],
            'one mapping, match with underscore replacement'                        => [
                ['Foo_' => [__DIR__]],
                new Identifier('Foo_Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/Foo/Bar.php'],
            ],
            'trailing and leading slash in mapping is trimmed'      => [
                ['Foo' => [__DIR__ . '/']],
                new Identifier('Foo\\Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/Foo/Bar.php'],
            ],
            'one mapping, match if class === prefix' => [
                ['Foo' => [__DIR__]],
                new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/Foo.php'],
            ],
            'multiple mappings, match when class !== prefix' => [
                [
                    'Foo_Baz' => [__DIR__ . '/../..'],
                    'Foo' => [__DIR__ . '/..'],
                ],
                new Identifier('Foo_Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/../Foo/Bar.php'],
            ],
        ];
    }

    /**
     * @dataProvider invalidMappings
     *
     * @param array[] $invalidMappings
     */
    public function testRejectsInvalidMappings(array $invalidMappings) : void
    {
        $this->expectException(InvalidPrefixMapping::class);

        Psr0Mapping::fromArrayMappings($invalidMappings);
    }

    /** @return array<string, array<int, array<string, array<int, string>>>> */
    public function invalidMappings() : array
    {
        return [
            'array contains empty prefixes'                            => [['' => 'bar']],
            'array contains empty paths'                               => [['foo' => ['']]],
            'array contains empty path list'                           => [['foo' => []]],
            'array contains path pointing to a file'                   => [['foo' => [tempnam(sys_get_temp_dir(), 'non_existing')]]],
            'array contains path pointing to a non-existing directory' => [['foo' => [sys_get_temp_dir() . '/' . uniqid('not_existing', true)]]],
        ];
    }
}
