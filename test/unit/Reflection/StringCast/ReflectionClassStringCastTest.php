<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Reflection\StringCast;

use Exception;
use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflection\ReflectionObject;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Rector\BetterReflectionTest\BetterReflectionSingleton;
use Rector\BetterReflectionTest\Fixture\StringCastClass;
use Rector\BetterReflectionTest\Fixture\StringCastClassObject;

/**
 * @covers \Rector\BetterReflection\Reflection\StringCast\ReflectionClassStringCast
 */
class ReflectionClassStringCastTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    public function testToString() : void
    {
        $reflector       = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastClass.php', $this->astLocator));
        $classReflection = $reflector->reflect(StringCastClass::class);

        self::assertStringMatchesFormat(
            \file_get_contents(__DIR__ . '/../../Fixture/StringCastClassExpected.txt'),
            $classReflection->__toString()
        );
    }

    public function testFinalClassToString() : void
    {
        $php = '<?php final class StringCastFinalClass {}';

        $reflector       = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflect('StringCastFinalClass');

        self::assertStringStartsWith('Class [ <user> final class StringCastFinalClass ]', (string) $classReflection);
    }

    public function testInternalClassToString() : void
    {
        $reflector       = new ClassReflector(new PhpInternalSourceLocator($this->astLocator));
        $classReflection = $reflector->reflect(Exception::class);

        self::assertStringStartsWith('Class [ <internal:Core> class Exception implements Throwable ]', (string) $classReflection);
    }

    public function testInterfaceToString() : void
    {
        $php = '<?php interface StringCastInterface {}';

        $reflector       = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflect('StringCastInterface');

        self::assertStringStartsWith('Interface [ <user> interface StringCastInterface ]', (string) $classReflection);
    }

    public function testTraitToString() : void
    {
        $php = '<?php trait StringCastTrait {}';

        $reflector       = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflect('StringCastTrait');

        self::assertStringStartsWith('Trait [ <user> trait StringCastTrait ]', (string) $classReflection);
    }

    public function testClassObjectToString() : void
    {
        $stringCastClassObjectFilename = __DIR__ . '/../../Fixture/StringCastClassObject.php';
        require_once $stringCastClassObjectFilename;

        $reflector       = new ClassReflector(new SingleFileSourceLocator($stringCastClassObjectFilename, $this->astLocator));
        $classReflection = $reflector->reflect(StringCastClassObject::class);

        $object = new StringCastClassObject();

        $object->dynamicProperty = 'string';

        $objectReflection = ReflectionObject::createFromInstance($object);

        self::assertStringMatchesFormat(
            \file_get_contents(__DIR__ . '/../../Fixture/StringCastClassObjectExpected.txt'),
            $objectReflection->__toString()
        );
    }
}
