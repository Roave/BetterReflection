<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutator;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Mutator\ReflectionClassMutator;
use Roave\BetterReflection\Reflection\Mutator\ReflectionFunctionAbstractMutator;
use Roave\BetterReflection\Reflection\Mutator\ReflectionMutators;
use Roave\BetterReflection\Reflection\Mutator\ReflectionParameterMutator;
use Roave\BetterReflection\Reflection\Mutator\ReflectionPropertyMutator;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutator\ReflectionMutators
 */
final class ReflectionMutatorsTest extends TestCase
{
    public function testAccessorsReturnTypes() : void
    {
        $reflectionMutators = new ReflectionMutators();

        self::assertInstanceOf(ReflectionClassMutator::class, $reflectionMutators->classMutator());
        self::assertInstanceOf(ReflectionFunctionAbstractMutator::class, $reflectionMutators->functionMutator());
        self::assertInstanceOf(ReflectionPropertyMutator::class, $reflectionMutators->propertyMutator());
        self::assertInstanceOf(ReflectionParameterMutator::class, $reflectionMutators->parameterMutator());
    }

    public function testProducedInstancesAreMemoized() : void
    {
        $reflectionMutators = new ReflectionMutators();

        self::assertSame($reflectionMutators->classMutator(), $reflectionMutators->classMutator());
        self::assertSame($reflectionMutators->functionMutator(), $reflectionMutators->functionMutator());
        self::assertSame($reflectionMutators->propertyMutator(), $reflectionMutators->propertyMutator());
        self::assertSame($reflectionMutators->parameterMutator(), $reflectionMutators->parameterMutator());
    }

    public function testProducedInstancesAreNotMemoizedAcrossInstances() : void
    {
        $reflectionMutators1 = new ReflectionMutators();
        $reflectionMutators2 = new ReflectionMutators();

        self::assertNotSame($reflectionMutators1->classMutator(), $reflectionMutators2->classMutator());
        self::assertNotSame($reflectionMutators1->functionMutator(), $reflectionMutators2->functionMutator());
        self::assertNotSame($reflectionMutators1->propertyMutator(), $reflectionMutators2->propertyMutator());
        self::assertNotSame($reflectionMutators1->parameterMutator(), $reflectionMutators2->parameterMutator());
    }
}
