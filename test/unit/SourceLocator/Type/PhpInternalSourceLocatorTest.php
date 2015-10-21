<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Located\InternalLocatedSource;
use BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use ReflectionClass as PhpReflectionClass;

/**
 * @covers \BetterReflection\SourceLocator\Type\PhpInternalSourceLocator
 */
class PhpInternalSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Reflector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockReflector()
    {
        return $this->getMock(Reflector::class);
    }

    /**
     * @dataProvider internalSymbolsProvider
     *
     * @param string $className
     */
    public function testCanFetchInternalLocatedSource($className)
    {
        $locator = new PhpInternalSourceLocator();

        try {
            /** @var ReflectionClass $reflection */
            $reflection = $locator->locateIdentifier(
                $this->getMockReflector(),
                new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
            );
            $source = $reflection->getLocatedSource();

            $this->assertInstanceOf(InternalLocatedSource::class, $source);
            $this->assertNotEmpty($source->getSource());
        } catch (\ReflectionException $e) {
            $this->markTestIncomplete(sprintf(
                'Can\'t reflect class "%s" due to an internal reflection exception: "%s". Consider adding a stub class',
                $className,
                $e->getMessage()
            ));
        }
    }

    /**
     * @dataProvider internalSymbolsProvider
     *
     * @param string $className
     * @throws \ReflectionException
     */
    public function testCanReflectInternalClasses($className)
    {
        /* @var $class */
        $phpInternalSourceLocator = new PhpInternalSourceLocator();
        $reflector = (new ClassReflector($phpInternalSourceLocator));

        try {
            $class = $reflector->reflect($className);
        } catch (\ReflectionException $e) {
            if ($phpInternalSourceLocator->hasStub($className)) {
                throw $e;
            }

            $this->markTestIncomplete(sprintf(
                'Can\'t reflect class "%s" due to an internal reflection exception: "%s". Consider adding a stub class',
                $className,
                $e->getMessage()
            ));
        }

        $this->assertInstanceOf(ReflectionClass::class, $class);
        $this->assertSame($className, $class->getName());
        $this->assertTrue($class->isInternal());
        $this->assertFalse($class->isUserDefined());

        $internalReflection = new \ReflectionClass($className);

        $this->assertSame($internalReflection->isInterface(), $class->isInterface());
        $this->assertSame($internalReflection->isTrait(), $class->isTrait());
    }

    /**
     * @return string[] internal symbols
     */
    public function internalSymbolsProvider()
    {
        $allSymbols = array_merge(
            get_declared_classes(),
            get_declared_interfaces(),
            get_declared_traits()
        );

        $indexedSymbols = array_combine($allSymbols, $allSymbols);

        return array_map(
            function ($symbol) {
                return [$symbol];
            },
            array_filter(
                $indexedSymbols,
                function ($symbol) {
                    $reflection = new PhpReflectionClass($symbol);

                    return $reflection->isInternal();
                }
            )
        );
    }

    public function testReturnsNullForNonExistentCode()
    {
        $locator = new PhpInternalSourceLocator();
        $this->assertNull(
            $locator->locateIdentifier(
                $this->getMockReflector(),
                new Identifier(
                    'Foo\Bar',
                    new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
                )
            )
        );
    }

    public function testReturnsNullForFunctions()
    {
        $locator = new PhpInternalSourceLocator();
        $this->assertNull(
            $locator->locateIdentifier(
                $this->getMockReflector(),
                new Identifier(
                    'foo',
                    new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
                )
            )
        );
    }
}
