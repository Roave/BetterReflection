<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Mutation;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Mutation\RemoveParameterType;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\Reflection\Mutation\RemoveParameterType
 */
class RemoveParameterTypeTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    /**
     * @var ClassReflector
     */
    private $classReflector;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator     = (new BetterReflection())->astLocator();
        $this->classReflector = $this->createMock(ClassReflector::class);
    }

    public function testWithType() : void
    {
        $php = '<?php function boo(string $a) {}';

        $reflector           = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $parameterReflection = $reflector->reflect('boo')->getParameter('a');

        $modifiedParameterReflection = (new RemoveParameterType())->__invoke($parameterReflection);

        self::assertInstanceOf(ReflectionParameter::class, $modifiedParameterReflection);
        self::assertNotSame($parameterReflection, $modifiedParameterReflection);
        self::assertTrue($parameterReflection->hasType());
        self::assertFalse($modifiedParameterReflection->hasType());
        self::assertNull($modifiedParameterReflection->getType());
    }

    public function testWithoutType() : void
    {
        $php = '<?php function boo($a) {}';

        $reflector           = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $parameterReflection = $reflector->reflect('boo')->getParameter('a');

        $modifiedParameterReflection = (new RemoveParameterType())->__invoke($parameterReflection);

        self::assertInstanceOf(ReflectionParameter::class, $modifiedParameterReflection);
        self::assertNotSame($parameterReflection, $modifiedParameterReflection);
        self::assertFalse($parameterReflection->hasType());
        self::assertFalse($modifiedParameterReflection->hasType());
        self::assertNull($modifiedParameterReflection->getType());
    }
}
