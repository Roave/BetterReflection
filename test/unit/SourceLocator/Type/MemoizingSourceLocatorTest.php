<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function count;
use function in_array;
use function random_int;
use function range;
use function spl_object_hash;
use function uniqid;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator
 */
class MemoizingSourceLocatorTest extends TestCase
{
    private Reflector|MockObject $reflector1;

    private Reflector|MockObject $reflector2;

    private SourceLocator|MockObject $wrappedLocator;

    private MemoizingSourceLocator $memoizingLocator;

    /** @var string[] */
    private array $identifierNames;

    private int $identifierCount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reflector1       = $this->createMock(Reflector::class);
        $this->reflector2       = $this->createMock(Reflector::class);
        $this->wrappedLocator   = $this->createMock(SourceLocator::class);
        $this->memoizingLocator = new MemoizingSourceLocator($this->wrappedLocator);
        $this->identifierNames  = array_unique(array_map(
            static function (): string {
                return uniqid('identifier', true);
            },
            range(1, 20),
        ));
        $this->identifierCount  = count($this->identifierNames);
    }

    public function testLocateIdentifierIsMemoized(): void
    {
        $this->assertMemoization(
            array_map(
                static function (string $identifier): Identifier {
                    return new Identifier(
                        $identifier,
                        new IdentifierType(
                            [IdentifierType::IDENTIFIER_CLASS, IdentifierType::IDENTIFIER_FUNCTION][random_int(0, 1)],
                        ),
                    );
                },
                $this->identifierNames,
            ),
            $this->identifierCount,
            [$this->reflector1],
        );
    }

    public function testLocateIdentifiersDistinguishesBetweenIdentifierTypes(): void
    {
        $classIdentifiers    = array_map(
            static function (string $identifier): Identifier {
                return new Identifier($identifier, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
            },
            $this->identifierNames,
        );
        $functionIdentifiers = array_map(
            static function (string $identifier): Identifier {
                return new Identifier($identifier, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));
            },
            $this->identifierNames,
        );

        $this->assertMemoization(
            array_merge($classIdentifiers, $functionIdentifiers),
            $this->identifierCount * 2,
            [$this->reflector1],
        );
    }

    public function testLocateIdentifiersDistinguishesBetweenReflectorInstances(): void
    {
        $this->assertMemoization(
            array_map(
                static function (string $identifier): Identifier {
                    return new Identifier(
                        $identifier,
                        new IdentifierType(
                            [IdentifierType::IDENTIFIER_CLASS, IdentifierType::IDENTIFIER_FUNCTION][random_int(0, 1)],
                        ),
                    );
                },
                $this->identifierNames,
            ),
            $this->identifierCount * 2,
            [$this->reflector1, $this->reflector2],
        );
    }

    public function testMemoizationByTypeDistinguishesBetweenSourceLocatorsAndType(): void
    {
        /** @var IdentifierType[] $types */
        $types    = [
            new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
        ];
        $symbols1 = [
            IdentifierType::IDENTIFIER_FUNCTION => [$this->createMock(Reflection::class)],
            IdentifierType::IDENTIFIER_CLASS    => [$this->createMock(Reflection::class)],
        ];
        $symbols2 = [
            IdentifierType::IDENTIFIER_FUNCTION => [$this->createMock(Reflection::class)],
            IdentifierType::IDENTIFIER_CLASS    => [$this->createMock(Reflection::class)],
        ];

        $this
            ->wrappedLocator
            ->expects(self::exactly(4))
            ->method('locateIdentifiersByType')
            ->with(self::logicalOr($this->reflector1, $this->reflector2))
            ->willReturnCallback(function (
                Reflector $reflector,
                IdentifierType $identifierType
            ) use (
                $symbols1,
                $symbols2
            ): array {
                if ($reflector === $this->reflector1) {
                    return $symbols1[$identifierType->getName()];
                }

                return $symbols2[$identifierType->getName()];
            });

        foreach ($types as $type) {
            self::assertSame(
                $symbols1[$type->getName()],
                $this->memoizingLocator->locateIdentifiersByType($this->reflector1, $type),
            );
            self::assertSame(
                $symbols2[$type->getName()],
                $this->memoizingLocator->locateIdentifiersByType($this->reflector2, $type),
            );

            // second execution - ensures that memoization is in place
            self::assertSame(
                $symbols1[$type->getName()],
                $this->memoizingLocator->locateIdentifiersByType($this->reflector1, $type),
            );
            self::assertSame(
                $symbols2[$type->getName()],
                $this->memoizingLocator->locateIdentifiersByType($this->reflector2, $type),
            );
        }
    }

    /**
     * @param Identifier[] $identifiers
     * @param Reflector[]  $reflectors
     */
    private function assertMemoization(
        array $identifiers,
        int $expectedFetchOperationsCount,
        array $reflectors
    ): void {
        $fetchedSymbolsCount = [];

        $this
            ->wrappedLocator
            ->expects(self::exactly($expectedFetchOperationsCount))
            ->method('locateIdentifier')
            ->with(
                self::logicalOr(...$reflectors),
                self::callback(static function (Identifier $identifier) use ($identifiers) {
                    return in_array($identifier, $identifiers, true);
                }),
            )
            ->willReturnCallback(function (
                Reflector $reflector,
                Identifier $identifier
            ) use (
                &$fetchedSymbolsCount
            ): ?Reflection {
                $identifierId = spl_object_hash($identifier);
                $reflectorId  = spl_object_hash($reflector);
                $hash         = $reflectorId . $identifierId;

                $fetchedSymbolsCount[$hash] = ($fetchedSymbolsCount[$hash] ?? 0) + 1;

                return [
                    $this->createMock(Reflection::class),
                    null,
                ][random_int(0, 1)];
            });

        $memoizedSymbols = $this->locateIdentifiers($reflectors, $identifiers);
        $cachedSymbols   = $this->locateIdentifiers($reflectors, $identifiers);

        self::assertCount($expectedFetchOperationsCount, $memoizedSymbols);

        foreach ($fetchedSymbolsCount as $fetchedSymbolCount) {
            self::assertSame(1, $fetchedSymbolCount, 'Each fetch is unique');
        }

        self::assertSame($memoizedSymbols, $cachedSymbols);

        $memoizedSymbolsIds = array_map('spl_object_hash', array_filter($memoizedSymbols));
        self::assertCount(count($memoizedSymbolsIds), array_unique($memoizedSymbolsIds), 'No duplicate symbols');
    }

    /**
     * @param Reflector[]  $reflectors
     * @param Identifier[] $identifiers
     *
     * @return Reflection[]|null[]
     */
    private function locateIdentifiers(array $reflectors, array $identifiers): array
    {
        $memoizedSymbols = [];

        foreach ($reflectors as $reflector) {
            foreach ($identifiers as $identifier) {
                $memoizedSymbols[] = $this->memoizingLocator->locateIdentifier($reflector, $identifier);
            }
        }

        return $memoizedSymbols;
    }
}
