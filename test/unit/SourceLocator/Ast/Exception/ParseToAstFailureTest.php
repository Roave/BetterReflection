<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast\Exception;

use Exception;
use PhpParser\Error;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure
 */
class ParseToAstFailureTest extends TestCase
{
    public function testErrorInTheMiddleOfSource(): void
    {
        $locatedSource = new LocatedSource(
            <<<'PHP'
            <?php
            /**
             * Some
             * very
             * long
             * comment
             */
            class SomeClass
            {
                public function __construct(foo)
                {
                    $this->foo = $foo;
                    $this->boo = 'boo';

                    // More code
                    // More code
                    // More code
                }
            }
            PHP,
            'Whatever',
        );

        $previous = new Error('Error message', ['startLine' => 10]);

        $exception = ParseToAstFailure::fromLocatedSource($locatedSource, $previous);

        self::assertInstanceOf(ParseToAstFailure::class, $exception);
        self::assertSame(
            <<<'ERROR'
            AST failed to parse in located source (line 10): Error message

             * long
             * comment
             */
            class SomeClass
            {
                public function __construct(foo)
                {
                    $this->foo = $foo;
                    $this->boo = 'boo';

                    // More code
            ERROR,
            $exception->getMessage(),
        );
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testErrorAtTheBeginningOfSource(): void
    {
        $locatedSource = new LocatedSource(
            <<<'PHP'
            <?php
            class SomeClass
            {
                // Code
                // Code
                // Code
                // Code
                // Code
            }
            PHP,
            'Whatever',
        );

        $previous = new Error('Error message', ['startLine' => 2]);

        $exception = ParseToAstFailure::fromLocatedSource($locatedSource, $previous);

        self::assertInstanceOf(ParseToAstFailure::class, $exception);
        self::assertSame(
            <<<'ERROR'
            AST failed to parse in located source (line 2): Error message

            <?php
            class SomeClass
            {
                // Code
                // Code
                // Code
                // Code
            ERROR,
            $exception->getMessage(),
        );
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testErrorAtTheEndOfSource(): void
    {
        $locatedSource = new LocatedSource(
            <<<'PHP'
            <?php

            // Comment
            // Comment
            // Comment
            // Comment
            // Comment

            class SomeClass
            {
            }
            PHP,
            'Whatever',
        );

        $previous = new Error('Error message', ['startLine' => 10]);

        $exception = ParseToAstFailure::fromLocatedSource($locatedSource, $previous);

        self::assertInstanceOf(ParseToAstFailure::class, $exception);
        self::assertSame(
            <<<'ERROR'
            AST failed to parse in located source (line 10): Error message

            // Comment
            // Comment
            // Comment

            class SomeClass
            {
            }
            ERROR,
            $exception->getMessage(),
        );
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testLocatedSourceWithoutFilename(): void
    {
        $locatedSource = new LocatedSource('<?php abc', 'Whatever');

        $previous = new Error('Error message', ['startLine' => 1]);

        $exception = ParseToAstFailure::fromLocatedSource($locatedSource, $previous);

        self::assertInstanceOf(ParseToAstFailure::class, $exception);
        self::assertSame(
            <<<'ERROR'
            AST failed to parse in located source (line 1): Error message

            <?php abc
            ERROR,
            $exception->getMessage(),
        );
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testLocatedSourceWithFilename(): void
    {
        $locatedSource = new LocatedSource('<?php abc', 'Whatever');

        $filenameProperty = new ReflectionProperty($locatedSource, 'filename');
        $filenameProperty->setAccessible(true);
        $filenameProperty->setValue($locatedSource, '/foo/bar');

        $previous = new Error('Some error message', ['startLine' => 1]);

        $exception = ParseToAstFailure::fromLocatedSource($locatedSource, $previous);

        self::assertInstanceOf(ParseToAstFailure::class, $exception);
        self::assertSame(
            <<<'ERROR'
            AST failed to parse in located source in file /foo/bar (line 1): Some error message

            <?php abc
            ERROR,
            $exception->getMessage(),
        );
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testErrorWithoutLine(): void
    {
        $locatedSource = new LocatedSource('<?php abc', 'Whatever');

        $previous = new Error('No line');

        $exception = ParseToAstFailure::fromLocatedSource($locatedSource, $previous);

        self::assertInstanceOf(ParseToAstFailure::class, $exception);
        self::assertSame('AST failed to parse in located source: No line', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testUnknownError(): void
    {
        $locatedSource = new LocatedSource('<?php abc', 'Whatever');

        $previous = new Exception('Unknown error');

        $exception = ParseToAstFailure::fromLocatedSource($locatedSource, $previous);

        self::assertInstanceOf(ParseToAstFailure::class, $exception);
        self::assertSame('AST failed to parse in located source: Unknown error', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }
}
