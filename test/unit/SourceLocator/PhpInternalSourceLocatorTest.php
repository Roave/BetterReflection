<?php

namespace BetterReflectionTest\SourceLocator;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\PhpInternalSourceLocator;
use ReflectionClass as PhpReflectionClass;

/**
 * @covers \BetterReflection\SourceLocator\PhpInternalSourceLocator
 */
class PhpInternalSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider internalSymbolsProvider
     *
     * @param string $className
     */
    public function testCanReflectInternalClasses($className)
    {
        /* @var $class */
        $reflector = (new ClassReflector(new PhpInternalSourceLocator()));

        try {
            $class = $reflector->reflect($className);
        } catch (\ReflectionException $e) {
            $this->markTestIncomplete(sprintf(
                'Can\'t reflect class "%s" due to an internal reflection exception: "%s". Consider adding a stub class',
                $className,
                $e->getMessage()
            ));
        }

        $this->assertInstanceOf(ReflectionClass::class, $class);
        $this->assertSame($className, $class->getName());
        //$this->assertTrue($class->isInternal());
        //$this->assertFalse($class->isUserDefined());
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
}
