<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflector;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use function assert;

class ConstantReflector implements Reflector
{
    /** @var SourceLocator */
    private $sourceLocator;

    /** @var ClassReflector */
    private $classReflector;

    public function __construct(SourceLocator $sourceLocator, ClassReflector $classReflector)
    {
        $this->sourceLocator  = $sourceLocator;
        $this->classReflector = $classReflector;
    }

    /**
     * Create a ReflectionConstant for the specified $constantName.
     *
     * @return ReflectionConstant
     *
     * @throws IdentifierNotFound
     */
    public function reflect(string $constantName) : Reflection
    {
        $identifier = new Identifier($constantName, new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT));

        $constantInfo = $this->sourceLocator->locateIdentifier($this->classReflector, $identifier);
        assert($constantInfo instanceof ReflectionConstant || $constantInfo === null);

        if ($constantInfo === null) {
            throw Exception\IdentifierNotFound::fromIdentifier($identifier);
        }

        return $constantInfo;
    }

    /**
     * Get all the constants available in the scope specified by the SourceLocator.
     *
     * @return array<int, ReflectionConstant>
     */
    public function getAllConstants() : array
    {
        /** @var array<int,ReflectionConstant> $allConstants */
        $allConstants = $this->sourceLocator->locateIdentifiersByType(
            $this,
            new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT)
        );

        return $allConstants;
    }
}
