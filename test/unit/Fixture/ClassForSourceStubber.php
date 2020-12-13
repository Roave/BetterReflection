<?php

namespace Roave\BetterReflectionTest\Fixture;

use JsonSerializable;
use Serializable;

class ParentClassForSourceStubber
{
    public function methodFromParentClass()
    {
    }
}

interface ImplementedInterfaceForSourceStubber extends JsonSerializable
{
    public function methodFromInterface();
}

trait UsedParentTraitForSourceStubber
{
    public $propertyFromParentTrait;

    public function methodFromParentTrait()
    {
    }
}

trait UsedTraitForSourceStubber
{
    use UsedParentTraitForSourceStubber;

    protected $propertyFromTrait;

    public function methodFromTrait()
    {
    }
}

trait UsedTraitToAliasForSourceStubber
{
    public function methodFromTraitToAlias()
    {
    }
}

/**
 * Class comment
 */
abstract class ClassForSourceStubber extends ParentClassForSourceStubber implements ImplementedInterfaceForSourceStubber, Serializable
{
    use UsedTraitForSourceStubber;
    use UsedTraitToAliasForSourceStubber {
        UsedTraitToAliasForSourceStubber::methodFromTraitToAlias as aliasMethodFromTrait;
    }

    /**
     * Constant comment
     */
    const CONSTANT_WITHOUT_VISIBILITY = 1;
    public const PUBLIC_CONSTANT = 0.0;
    protected const PROTECTED_CONSTANT = 'string';
    private const PRIVATE_CONSTANT = [1, 2, 3];

    var $propertyWithoutVisibility = 0;

    /**
     * @var int|float|\stdClass
     */
    private $privateProperty = 1.1;

    /**
     * @var bool|bool[]|bool[][]
     */
    protected $protectedProperty = false;

    /**
     * @var string
     */
    public $publicProperty = 'string';

    public static $publicStaticProperty;

    public int $propertyWithTypeHint;

    function methodWithoutVisibility() : ?\stdClass
    {
    }

    /**
     * Method comment
     */
    public function publicMethod() : bool
    {
    }

    public function protectedMethod() : int
    {
    }

    public function privateMethod() : ?string
    {
    }

    public static function publicStaticMethod() : void
    {
    }

    abstract public function publicAbstractMethod();

    final public function publicFinalMethod() : float
    {
    }

    public function methodWithParameters($string, $int, $float, $bool, $iterable, $callable) : void
    {
    }

    public function methodWithParametersWithTypes(string $string, int $int, float $float, bool $bool, iterable $iterable, callable $callable) : void
    {
    }

    public function methodWithParametersWithNullableTypes(?string $string, ?int $int, ?float $float, ?bool $bool, ?iterable $iterable, ?callable $callable) : void
    {
    }

    public function methodWithOptionalParameters(string $string = 'string', int $int = 123, float $float = 0.0, bool $bool = true, iterable $iterable = [], ?callable $callable = null) : void
    {
    }

    public function methodWithSelfAndParentParameters(self $self, parent $parent) : void
    {
    }

    public function methodWithVariadicParameter(string ...$variadic) : void
    {
    }

    public function methodWithParameterPassedByReference(bool &$bool) : void
    {
    }

    public function &methodReturnsReference() : array
    {
    }

    public function methodWithStaticReturnValue() : static
    {
    }

    public function methodWithMixedReturnValue() : mixed
    {
    }
}
