<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast\Exception;

use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure
 */
class ParseToAstFailureTest extends TestCase
{
    public function testFromLocatedSourceWithoutFilename(): void
    {
        $locatedSource = new LocatedSource('<?php class SomeClass {}', 'Whatever');

        $previous = new Exception();

        $exception = ParseToAstFailure::fromLocatedSource($locatedSource, $previous);

        self::assertInstanceOf(ParseToAstFailure::class, $exception);
        self::assertSame('AST failed to parse in located source (first 20 characters: <?php class SomeClas)', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testFromLocatedSourceWithFilename(): void
    {
        $locatedSource = new LocatedSource('<?php abc', 'Whatever');

        $filenameProperty = new ReflectionProperty($locatedSource, 'filename');
        $filenameProperty->setAccessible(true);
        $filenameProperty->setValue($locatedSource, '/foo/bar');

        $previous = new Exception();

        $exception = ParseToAstFailure::fromLocatedSource($locatedSource, $previous);

        self::assertInstanceOf(ParseToAstFailure::class, $exception);
        self::assertSame('AST failed to parse in located source (in /foo/bar)', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }
}
