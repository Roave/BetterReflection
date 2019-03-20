<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Psr;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping
 */
class Psr4MappingTest extends TestCase
{
    /**
     * @dataProvider mappings
     *
     * @param array[]  $mappings
     * @param string[] $expectedDirectories
     */
    public function testExpectedDirectories(array $mappings, array $expectedDirectories) : void
    {
        self::assertEquals($expectedDirectories, Psr4Mapping::fromArrayMappings($mappings)->directories());
    }

    /**
     * @dataProvider mappings
     *
     * @param array[] $mappings
     */
    public function testIdempotentConstructor(array $mappings) : void
    {
        self::assertEquals(Psr4Mapping::fromArrayMappings($mappings), Psr4Mapping::fromArrayMappings($mappings));
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
            Psr4Mapping::fromArrayMappings($mappings)->resolvePossibleFilePaths($identifier)
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
            'one mapping, match'                        => [
                ['Foo\\' => [__DIR__]],
                new Identifier('Foo\\Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/Bar.php'],
            ],
            'trailing and leading slash in mapping is trimmed'      => [
                ['Foo' => [__DIR__ . '/']],
                new Identifier('Foo\\Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/Bar.php'],
            ],
            'one mapping, no match if class === prefix' => [
                ['Foo' => [__DIR__]],
                new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [],
            ],
            'multiple mappings, match when class !== prefix' => [
                [
                    'Foo\\Bar' => [__DIR__ . '/../..'],
                    'Foo' => [__DIR__ . '/..'],
                ],
                new Identifier('Foo\\Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/../Bar.php'],
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
        $this->expectException(InvalidArgumentException::class);

        Psr4Mapping::fromArrayMappings($invalidMappings);
    }

    /** @return array[][] */
    public function invalidMappings() : array
    {
        return [
            'array contains integer prefixes'                          => [[1 => ['foo']]],
            'array contains non-array mappings'                        => [['foo' => 'bar']],
            'array contains empty prefixes'                            => [['' => 'bar']],
            'array contains empty paths'                               => [['foo' => ['']]],
            'array contains empty path list'                           => [['foo' => []]],
            'array contains path pointing to a file'                   => [['foo' => [tempnam(sys_get_temp_dir(), 'non_existing')]]],
            'array contains path pointing to a non-existing directory' => [['foo' => [sys_get_temp_dir() . '/' . uniqid('not_existing', true)]]],
        ];
    }
}
