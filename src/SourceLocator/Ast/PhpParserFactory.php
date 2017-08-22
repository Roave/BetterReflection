<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast;

use PhpParser\Parser;
use PhpParser\ParserFactory;

class PhpParserFactory
{

    public static function create(): Parser
    {
        return (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
    }

}
