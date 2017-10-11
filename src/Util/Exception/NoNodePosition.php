<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Util\Exception;

use InvalidArgumentException;
use PhpParser\Lexer;
use PhpParser\Node;

class NoNodePosition extends InvalidArgumentException
{
    public static function fromNode(Node $node) : self
    {
        return new self(\sprintf('%s doesn\'t contain position. Your %s is not configured properly', \get_class($node), Lexer::class));
    }
}
