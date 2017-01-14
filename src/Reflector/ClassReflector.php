<?php

namespace Roave\BetterReflection\Reflector;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflection\Context\ContextFactory;
use Roave\BetterReflection\Context\PhpDocumentorContextFactory;
use Roave\BetterReflection\Context\CachedContextFactory;

class ClassReflector implements Reflector
{
    /**
     * @var SourceLocator
     */
    private $sourceLocator;

    /**
     * @var ContextFactory
     */
    private $contextFactory;

    /**
     * @param SourceLocator $sourceLocator
     * @param ContextFactory $contextFactory
     */
    public function __construct(SourceLocator $sourceLocator, ContextFactory $contextFactory = null)
    {
        $this->sourceLocator = $sourceLocator;
        $this->contextFactory = $contextFactory ?: new PhpDocumentorContextFactory(new PhpDocumentorContextFactory());
    }

    /**
     * @return self
     */
    public static function buildDefaultReflector()
    {
        return new self(new AggregateSourceLocator([
            new PhpInternalSourceLocator(),
            new EvaledCodeSourceLocator(),
            new AutoloadSourceLocator(),
        ]), new CachedContextFactory(new PhpDocumentorContextFactory()));
    }

    /**
     * Create a ReflectionClass for the specified $className.
     *
     * @param string $className
     * @return \Roave\BetterReflection\Reflection\ReflectionClass
     * @throws \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound
     */
    public function reflect($className)
    {
        $identifier = new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $classInfo = $this->sourceLocator->locateIdentifier($this, $identifier);

        if (null === $classInfo) {
            throw Exception\IdentifierNotFound::fromIdentifier($identifier);
        }

        return $classInfo;
    }

    /**
     * Get all the classes available in the scope specified by the SourceLocator.
     *
     * @return \Roave\BetterReflection\Reflection\ReflectionClass[]
     */
    public function getAllClasses()
    {
        return $this->sourceLocator->locateIdentifiersByType(
            $this,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        );
    }

    /**
     * Return the class context factory.
     *
     * @return ContextFactory
     */
    public function getContextFactory()
    {
        return $this->contextFactory;
    }
}
