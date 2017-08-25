<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast;

use PhpParser\Lexer;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class PhpParserFactory
{

    public static function create(): Parser
    {
        $lexer = new Lexer\Emulative([
            'usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'],
        ]);
        return (new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer);
    }

}
