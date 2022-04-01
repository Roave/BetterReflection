<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflector;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

use function assert;

final class DefaultReflector implements Reflector
{
    public function __construct(private SourceLocator $sourceLocator)
    {
    }

    /**
     * Create a ReflectionClass for the specified $className.
     *
     * @throws IdentifierNotFound
     */
    public function reflectClass(string $identifierName): ReflectionClass
    {
        $identifier = new Identifier($identifierName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $classInfo = $this->sourceLocator->locateIdentifier($this, $identifier);

        if ($classInfo === null) {
            throw Exception\IdentifierNotFound::fromIdentifier($identifier);
        }

        assert($classInfo instanceof ReflectionClass);

        return $classInfo;
    }

    /**
     * Get all the classes available in the scope specified by the SourceLocator.
     *
     * @return list<ReflectionClass>
     */
    public function reflectAllClasses(): iterable
    {
        /** @var list<ReflectionClass> $allClasses */
        $allClasses = $this->sourceLocator->locateIdentifiersByType(
            $this,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
        );

        return $allClasses;
    }

    /**
     * Create a ReflectionFunction for the specified $functionName.
     *
     * @throws IdentifierNotFound
     */
    public function reflectFunction(string $identifierName): ReflectionFunction
    {
        $identifier = new Identifier($identifierName, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));

        $functionInfo = $this->sourceLocator->locateIdentifier($this, $identifier);

        if ($functionInfo === null) {
            throw Exception\IdentifierNotFound::fromIdentifier($identifier);
        }

        assert($functionInfo instanceof ReflectionFunction);

        return $functionInfo;
    }

    /**
     * Get all the functions available in the scope specified by the SourceLocator.
     *
     * @return list<ReflectionFunction>
     */
    public function reflectAllFunctions(): iterable
    {
        /** @var list<ReflectionFunction> $allFunctions */
        $allFunctions = $this->sourceLocator->locateIdentifiersByType(
            $this,
            new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
        );

        return $allFunctions;
    }

    /**
     * Create a ReflectionConstant for the specified $constantName.
     *
     * @throws IdentifierNotFound
     */
    public function reflectConstant(string $identifierName): ReflectionConstant
    {
        $identifier = new Identifier($identifierName, new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT));

        $constantInfo = $this->sourceLocator->locateIdentifier($this, $identifier);

        if ($constantInfo === null) {
            throw Exception\IdentifierNotFound::fromIdentifier($identifier);
        }

        assert($constantInfo instanceof ReflectionConstant);

        return $constantInfo;
    }

    /**
     * Get all the constants available in the scope specified by the SourceLocator.
     *
     * @return list<ReflectionConstant>
     */
    public function reflectAllConstants(): iterable
    {
        /** @var list<ReflectionConstant> $allConstants */
        $allConstants = $this->sourceLocator->locateIdentifiersByType(
            $this,
            new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT),
        );

        return $allConstants;
    }
}
