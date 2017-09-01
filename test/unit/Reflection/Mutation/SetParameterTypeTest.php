<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutation;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutation\SetParameterType;
use Roave\BetterReflection\Reflection\Mutator\ReflectionParameterMutator;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\Reflection\Mutator\ReflectionMutatorsSingleton;
use Traversable;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutation\SetParameterType
 */
class SetParameterTypeTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    /**
     * @var ClassReflector
     */
    private $classReflector;

    /**
     * @var ReflectionParameterMutator
     */
    private $parameterMutator;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator       = (new BetterReflection())->astLocator();
        $this->classReflector   = $this->createMock(ClassReflector::class);
        $this->parameterMutator = ReflectionMutatorsSingleton::instance()->parameterMutator();
    }

    public function testWithType() : void
    {
        $php = '<?php function boo(string $a) {}';

        $reflector           = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $parameterReflection = $reflector->reflect('boo')->getParameter('a');

        $modifiedParameterReflection = (new SetParameterType($this->parameterMutator))->__invoke($parameterReflection, 'int', false);

        self::assertInstanceOf(ReflectionParameter::class, $modifiedParameterReflection);
        self::assertNotSame($parameterReflection, $modifiedParameterReflection);
        self::assertTrue($modifiedParameterReflection->hasType());
        self::assertNotSame((string) $parameterReflection->getType(), (string) $modifiedParameterReflection->getType());
        self::assertSame('int', (string) $modifiedParameterReflection->getType());
        self::assertFalse($modifiedParameterReflection->getType()->allowsNull());
    }

    public function testWithoutType() : void
    {
        $php = '<?php function boo($a) {}';

        $reflector           = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $parameterReflection = $reflector->reflect('boo')->getParameter('a');

        $modifiedParameterReflection = (new SetParameterType($this->parameterMutator))->__invoke($parameterReflection, 'bool', false);

        self::assertInstanceOf(ReflectionParameter::class, $modifiedParameterReflection);
        self::assertNotSame($parameterReflection, $modifiedParameterReflection);
        self::assertTrue($modifiedParameterReflection->hasType());
        self::assertSame('bool', (string) $modifiedParameterReflection->getType());
        self::assertFalse($modifiedParameterReflection->getType()->allowsNull());
    }

    public function testNullableType() : void
    {
        $php = '<?php function boo($a) {}';

        $reflector           = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $parameterReflection = $reflector->reflect('boo')->getParameter('a');

        $modifiedParameterReflection = (new SetParameterType($this->parameterMutator))->__invoke($parameterReflection, 'string', true);

        self::assertInstanceOf(ReflectionParameter::class, $modifiedParameterReflection);
        self::assertNotSame($parameterReflection, $modifiedParameterReflection);
        self::assertTrue($modifiedParameterReflection->hasType());
        self::assertSame('string', (string) $modifiedParameterReflection->getType());
        self::assertTrue($modifiedParameterReflection->getType()->allowsNull());
    }

    public function testClassType() : void
    {
        $php = '<?php function boo($a) {}';

        $reflector           = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $parameterReflection = $reflector->reflect('boo')->getParameter('a');

        $modifiedParameterReflection = (new SetParameterType($this->parameterMutator))->__invoke($parameterReflection, Traversable::class, false);

        self::assertInstanceOf(ReflectionParameter::class, $modifiedParameterReflection);
        self::assertNotSame($parameterReflection, $modifiedParameterReflection);
        self::assertTrue($modifiedParameterReflection->hasType());
        self::assertSame(Traversable::class, (string) $modifiedParameterReflection->getType());
    }
}
