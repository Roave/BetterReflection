<?php

namespace BetterReflectionTest\Reflector\Exception;

use BetterReflection\SourceLocator\Ast\Exception\AstParse;
use BetterReflection\SourceLocator\Located\LocatedSource;

/**
 * @covers \BetterReflection\SourceLocator\Ast\Exception\AstParse
 */
class AstParseTest extends \PHPUnit_Framework_TestCase
{
    public function testFromLocatedSourceWithoutFilename()
    {
        $locatedSource = new LocatedSource('<?php abc', null);

        $previous = new \Exception();

        $exception = AstParse::fromLocatedSource($locatedSource, $previous);

        $this->assertInstanceOf(AstParse::class, $exception);
        $this->assertSame('AST failed to parse in located source (first 20 characters: <?php abc)', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testFromLocatedSourceWithFilename()
    {
        $locatedSource = new LocatedSource('<?php abc', null);

        $filenameProperty = new \ReflectionProperty($locatedSource, 'filename');
        $filenameProperty->setAccessible(true);
        $filenameProperty->setValue($locatedSource, '/foo/bar');

        $previous = new \Exception();

        $exception = AstParse::fromLocatedSource($locatedSource, $previous);

        $this->assertInstanceOf(AstParse::class, $exception);
        $this->assertSame('AST failed to parse in located source (in /foo/bar)', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
