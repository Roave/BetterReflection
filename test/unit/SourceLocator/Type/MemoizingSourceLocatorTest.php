<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use IntlGregorianCalendar;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionParameter as CoreReflectionParameter;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator
 */
class MemoizingSourceLocatorTest extends TestCase
{
    /**
     * @var Reflector|\PHPUnit_Framework_MockObject_MockObject
     */
    private $reflector1;

    /**
     * @var Reflector|\PHPUnit_Framework_MockObject_MockObject
     */
    private $reflector2;

    /**
     * @var SourceLocator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $wrappedLocator;

    /**
     * @var MemoizingSourceLocator
     */
    private $memoizingLocator;

    /**
     * @var string[]
     */
    private $identifierNames;

    /**
     * @var int
     */
    private $identifierCount;

    protected function setUp() : void
    {
        parent::setUp();

        $this->reflector1       = $this->createMock(Reflector::class);
        $this->reflector2       = $this->createMock(Reflector::class);
        $this->wrappedLocator   = $this->createMock(SourceLocator::class);
        $this->memoizingLocator = new MemoizingSourceLocator($this->wrappedLocator);
        $this->identifierNames  = array_unique(array_map(
            function () : string {
                return uniqid('identifer', true);
            },
            range(1, 100)
        ));
        $this->identifierCount  = count($this->identifierNames);
    }

    public function testLocateIdentifierIsMemoized() : void
    {
        $this->assertMemoization(
            array_map(
                function (string $identifier) : Identifier {
                    return new Identifier(
                        $identifier,
                        new IdentifierType(
                            [IdentifierType::IDENTIFIER_CLASS, IdentifierType::IDENTIFIER_FUNCTION][random_int(0, 1)]
                        )
                    );
                },
                $this->identifierNames
            ),
            $this->identifierCount
        );
    }

    public function testLocateIdentifiersDistinguishesBetweenIdentifierTypes() : void
    {
        $classIdentifiers = array_map(
            function (string $identifier) : Identifier {
                return new Identifier($identifier, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
            },
            $this->identifierNames
        );
        $functionIdentifiers = array_map(
            function (string $identifier) : Identifier {
                return new Identifier($identifier, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));
            },
            $this->identifierNames
        );

        $this->assertMemoization(
            array_merge($classIdentifiers, $functionIdentifiers),
            $this->identifierCount * 2
        );
    }

    /**
     * @param Identifier[] $identifiers
     * @param int          $expectedFetchOperationsCount
     * @param int[]        $fetchedSymbols
     */
    private function assertMemoization(
        array $identifiers,
        int $expectedFetchOperationsCount
    ) : void {
        $fetchedSymbolsCount = [];
        $locatedSymbols      = array_combine(
            array_map('spl_object_hash', $identifiers),
            array_map(
                function () : ?Reflection {
                    return [
                               $this->createMock(Reflection::class),
                               null
                           ][random_int(0, 1)];
                },
                $identifiers
            )
        );

        $this
            ->wrappedLocator
            ->expects(self::any())
            ->method('locateIdentifier')
            ->with(
                $this->reflector1,
                self::callback(function (Identifier $identifier) use ($identifiers) {
                    return \in_array($identifier, $identifiers, true);
                })
            )
            ->willReturnCallback(function (Reflector $ignored, Identifier $identifier) use (
                $locatedSymbols,
                & $fetchedSymbolsCount
            ) : ?Reflection {
                $identifierId = \spl_object_hash($identifier);

                $fetchedSymbolsCount[$identifierId] = ($fetchedSymbolsCount[$identifierId] ?? 0) + 1;

                return $locatedSymbols[$identifierId];
            });

        $identifiersCount = count($identifiers);
        $memoizedSymbols  = array_map(
            [$this->memoizingLocator, 'locateIdentifier'],
            array_fill(0, $identifiersCount, $this->reflector1),
            $identifiers
        );

        // repeated operation - for sport
        $cachedSymbols = array_map(
            [$this->memoizingLocator, 'locateIdentifier'],
            array_fill(0, $identifiersCount, $this->reflector1),
            $identifiers
        );

        self::assertCount($expectedFetchOperationsCount, $memoizedSymbols);
        self::assertSame($expectedFetchOperationsCount, array_sum($fetchedSymbolsCount));

        foreach ($fetchedSymbolsCount as $fetchedSymbolCount) {
            self::assertSame(1, $fetchedSymbolCount);
        }

        self::assertSame($memoizedSymbols, $cachedSymbols);
    }
}
