<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator;

use PhpParser\Lexer\Emulative;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Roave\BetterReflectionTest\Foo;

class AutoloadSourceLocatorWithoutParserTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testWithoutParser() : void
    {
    	//class_exists(\Roave\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser::class);
        $parser            = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, new Emulative([
            'usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'],
        ]));
        $functionReflector = null;
        $astLocator        = new AstLocator($parser, static function () use (&$functionReflector) : FunctionReflector {
            return $functionReflector;
        });
        $sourceLocator     = new AutoloadSourceLocator(
            $astLocator,
            $parser
        );
        $classReflector    = new ClassReflector($sourceLocator);
        $functionReflector = new FunctionReflector($sourceLocator, $classReflector);
        $reflection        = $classReflector->reflect(Foo::class);
        $this->assertSame(Foo::class, $reflection->getName());
    }
}
