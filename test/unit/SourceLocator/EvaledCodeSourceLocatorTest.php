<?php

namespace BetterReflectionTest\SourceLocator;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\EvaledCodeSourceLocator;
use BetterReflection\SourceLocator\EvaledLocatedSource;
use BetterReflection\SourceLocator\InternalLocatedSource;
use BetterReflection\SourceLocator\PhpInternalSourceLocator;
use ReflectionClass as PhpReflectionClass;

/**
 * @covers \BetterReflection\SourceLocator\EvaledCodeSourceLocator
 */
class EvaledCodeSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function testCanReflectEvaledClass()
    {
        $className = uniqid('foo');

        eval('class ' . $className . ' {function foo() {}}');

        $locator = new EvaledCodeSourceLocator();

        $source = $locator->__invoke(
            new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        );

        $this->assertInstanceOf(EvaledLocatedSource::class, $source);
        $this->assertNotEmpty($source->getSource());
    }
}
