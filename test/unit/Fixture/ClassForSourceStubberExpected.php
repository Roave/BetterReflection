<?php
namespace Rector\BetterReflectionTest\Fixture;

/**
 * Class comment
 */
abstract class ClassForSourceStubber extends \Rector\BetterReflectionTest\Fixture\ParentClassForSourceStubber implements \Rector\BetterReflectionTest\Fixture\ImplementedInterfaceForSourceStubber, \Serializable
{
    use \Rector\BetterReflectionTest\Fixture\UsedTraitToAliasForSourceStubber {
        \Rector\BetterReflectionTest\Fixture\UsedTraitToAliasForSourceStubber::methodFromTraitToAlias as aliasMethodFromTrait;
    }
    use \Rector\BetterReflectionTest\Fixture\UsedTraitForSourceStubber;
    /**
     * Constant comment
     */
    public const CONSTANT_WITHOUT_VISIBILITY = 1;
    public const PUBLIC_CONSTANT = 0.0;
    protected const PROTECTED_CONSTANT = 'string';
    private const PRIVATE_CONSTANT = [1, 2, 3];
    public $propertyWithoutVisibility = 0;
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
    public static $publicStaticProperty = null;
    public function methodWithoutVisibility() : ?\stdClass
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
    public abstract function publicAbstractMethod();
    public final function publicFinalMethod() : float
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
}
