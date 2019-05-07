<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use function array_filter;
use function array_map;
use function array_merge;
use function get_declared_classes;
use function get_declared_interfaces;
use function get_declared_traits;
use function get_defined_functions;
use function sprintf;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator
 */
class PhpInternalSourceLocatorTest extends TestCase
{
    /** @var PhpInternalSourceLocator */
    private $phpInternalSourceLocator;

    protected function setUp() : void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->phpInternalSourceLocator = new PhpInternalSourceLocator(
            $betterReflection->astLocator(),
            $betterReflection->sourceStubber()
        );
    }

    /**
     * @return Reflector|MockObject
     */
    private function getMockReflector()
    {
        return $this->createMock(Reflector::class);
    }

    /**
     * @dataProvider internalClassesProvider
     */
    public function testCanFetchInternalLocatedSourceForClasses(string $className) : void
    {
        try {
            /** @var ReflectionClass $reflection */
            $reflection = $this->phpInternalSourceLocator->locateIdentifier(
                $this->getMockReflector(),
                new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
            );
            $source     = $reflection->getLocatedSource();

            self::assertInstanceOf(InternalLocatedSource::class, $source);
            self::assertNotEmpty($source->getSource());
        } catch (ReflectionException $e) {
            self::markTestIncomplete(sprintf(
                'Can\'t reflect class "%s" due to an internal reflection exception: "%s".',
                $className,
                $e->getMessage()
            ));
        }
    }

    /**
     * @return string[][]
     */
    public function internalClassesProvider() : array
    {
        $allSymbols = array_merge(
            get_declared_classes(),
            get_declared_interfaces(),
            get_declared_traits()
        );

        return array_map(
            static function (string $symbol) : array {
                return [$symbol];
            },
            array_filter(
                $allSymbols,
                static function (string $symbol) : bool {
                    $reflection = new CoreReflectionClass($symbol);

                    return $reflection->isInternal();
                }
            )
        );
    }

    /**
     * @dataProvider internalFunctionsProvider
     */
    public function testCanFetchInternalLocatedSourceForFunctions(string $functionName) : void
    {
        try {
            /** @var ReflectionFunction $reflection */
            $reflection = $this->phpInternalSourceLocator->locateIdentifier(
                $this->getMockReflector(),
                new Identifier($functionName, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))
            );
            $source     = $reflection->getLocatedSource();

            self::assertInstanceOf(InternalLocatedSource::class, $source);
            self::assertNotEmpty($source->getSource());
        } catch (ReflectionException $e) {
            self::markTestIncomplete(sprintf(
                'Can\'t reflect function "%s" due to an internal reflection exception: "%s".',
                $functionName,
                $e->getMessage()
            ));
        }
    }

    /**
     * @return string[][]
     */
    public function internalFunctionsProvider() : array
    {
        $allSymbols = get_defined_functions()['internal'];

        return array_map(
            static function (string $symbol) : array {
                return [$symbol];
            },
            $allSymbols
        );
    }

    public function testReturnsNullForNonExistentClass() : void
    {
        self::assertNull(
            $this->phpInternalSourceLocator->locateIdentifier(
                $this->getMockReflector(),
                new Identifier(
                    'Foo\Bar',
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
                )
            )
        );
    }

    public function testReturnsNullForNonExistentFunction() : void
    {
        self::assertNull(
            $this->phpInternalSourceLocator->locateIdentifier(
                $this->getMockReflector(),
                new Identifier(
                    'foo',
                    new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
                )
            )
        );
    }
}
