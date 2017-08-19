<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflector;

use PhpParser\ParserFactory;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser;
use Roave\BetterReflection\SourceLocator\Ast\PhpParserLocator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class ClassReflector implements Reflector
{
    /**
     * @var SourceLocator
     */
    private $sourceLocator;

    /**
     * @param SourceLocator $sourceLocator
     */
    public function __construct(SourceLocator $sourceLocator)
    {
        $this->sourceLocator = $sourceLocator;
    }

    /**
     * @return self
     */
    public static function buildDefaultReflector() : self
    {
        $locator = new PhpParserLocator(
            new MemoizingParser((new ParserFactory())->create(ParserFactory::PREFER_PHP7))
        );

        return new self(new MemoizingSourceLocator(new AggregateSourceLocator([
            new PhpInternalSourceLocator($locator),
            new EvaledCodeSourceLocator($locator),
            new AutoloadSourceLocator($locator),
        ])));
    }

    /**
     * Create a ReflectionClass for the specified $className.
     *
     * @param string $className
     * @return \Roave\BetterReflection\Reflection\ReflectionClass|Reflection
     * @throws \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound
     */
    public function reflect(string $className) : Reflection
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
    public function getAllClasses() : array
    {
        return $this->sourceLocator->locateIdentifiersByType(
            $this,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        );
    }
}
