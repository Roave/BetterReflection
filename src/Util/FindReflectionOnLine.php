<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use PhpParser\Parser;
use PhpParser\ParserFactory;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser;
use Roave\BetterReflection\SourceLocator\Ast\PhpParserLocator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

final class FindReflectionOnLine
{
    /**
     * @var SourceLocator
     */
    private $sourceLocator;

    /**
     * @var Locator
     */
    private $locator;

    public function __construct(SourceLocator $sourceLocator, ?Locator $locator = null)
    {
        $this->sourceLocator = $sourceLocator;
        $this->locator       = $locator ?? new PhpParserLocator(
            new MemoizingParser((new ParserFactory())->create(ParserFactory::PREFER_PHP7))
        );
    }

    /**
     * @return self
     */
    public static function buildDefaultFinder() : self
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
     * Find a reflection on the specified line number.
     *
     * Returns null if no reflections found on the line.
     *
     * @param string $filename
     * @param int $lineNumber
     * @return ReflectionMethod|ReflectionClass|ReflectionFunction|Reflection|null
     * @throws \Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation
     * @throws \Roave\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure
     * @throws \InvalidArgumentException
     */
    public function __invoke(string $filename, int $lineNumber)
    {
        $lineNumber = (int)$lineNumber;
        $reflections = $this->computeReflections($filename);

        foreach ($reflections as $reflection) {
            if ($reflection instanceof ReflectionClass && $this->containsLine($reflection, $lineNumber)) {
                foreach ($reflection->getMethods() as $method) {
                    if ($this->containsLine($method, $lineNumber)) {
                        return $method;
                    }
                }
                return $reflection;
            }

            if ($reflection instanceof ReflectionFunction && $this->containsLine($reflection, $lineNumber)) {
                return $reflection;
            }
        }

        return null;
    }

    /**
     * Find all class and function reflections in the specified file
     *
     * @param string $filename
     * @return Reflection[]
     * @throws \Roave\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure
     * @throws \Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation
     */
    private function computeReflections(string $filename) : array
    {
        $singleFileSourceLocator = new SingleFileSourceLocator($filename, $this->locator);
        $reflector = new ClassReflector(new AggregateSourceLocator([$singleFileSourceLocator, $this->sourceLocator]));

        return array_merge(
            $singleFileSourceLocator->locateIdentifiersByType($reflector, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
            $singleFileSourceLocator->locateIdentifiersByType($reflector, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))
        );
    }

    /**
     * Check to see if the line is within the boundaries of the reflection specified.
     *
     * @param ReflectionMethod|ReflectionClass|ReflectionFunction|Reflection $reflection
     * @param int $lineNumber
     * @return bool
     * @throws \InvalidArgumentException
     */
    private function containsLine($reflection, int $lineNumber) : bool
    {
        if (!method_exists($reflection, 'getStartLine')) {
            throw new \InvalidArgumentException('Reflection does not have getStartLine method');
        }

        if (!method_exists($reflection, 'getEndLine')) {
            throw new \InvalidArgumentException('Reflection does not have getEndLine method');
        }

        return $lineNumber >= $reflection->getStartLine() && $lineNumber <= $reflection->getEndLine();
    }
}
