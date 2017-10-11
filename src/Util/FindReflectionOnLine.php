<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Util;

use InvalidArgumentException;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflection\Reflection;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Reflection\ReflectionFunction;
use Rector\BetterReflection\Reflection\ReflectionMethod;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\SourceLocator;

final class FindReflectionOnLine
{
    /**
     * @var SourceLocator
     */
    private $sourceLocator;

    /**
     * @var Locator
     */
    private $astLocator;

    public function __construct(SourceLocator $sourceLocator, Locator $astLocator)
    {
        $this->sourceLocator = $sourceLocator;
        $this->astLocator    = $astLocator;
    }

    /**
     * Find a reflection on the specified line number.
     *
     * Returns null if no reflections found on the line.
     *
     * @param string $filename
     * @param int $lineNumber
     * @return ReflectionMethod|ReflectionClass|ReflectionFunction|Reflection|null
     * @throws \Rector\BetterReflection\SourceLocator\Exception\InvalidFileLocation
     * @throws \Rector\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure
     * @throws \InvalidArgumentException
     */
    public function __invoke(string $filename, int $lineNumber)
    {
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
     * @return Reflection[]
     *
     * @throws \Rector\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure
     * @throws \Rector\BetterReflection\SourceLocator\Exception\InvalidFileLocation
     */
    private function computeReflections(string $filename) : array
    {
        $singleFileSourceLocator = new SingleFileSourceLocator($filename, $this->astLocator);
        $reflector               = new ClassReflector(new AggregateSourceLocator([$singleFileSourceLocator, $this->sourceLocator]));

        return \array_merge(
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
        if ( ! \method_exists($reflection, 'getStartLine')) {
            throw new InvalidArgumentException('Reflection does not have getStartLine method');
        }

        if ( ! \method_exists($reflection, 'getEndLine')) {
            throw new InvalidArgumentException('Reflection does not have getEndLine method');
        }

        return $lineNumber >= $reflection->getStartLine() && $lineNumber <= $reflection->getEndLine();
    }
}
