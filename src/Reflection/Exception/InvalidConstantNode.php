<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Exception;

use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;
use RuntimeException;

use function sprintf;
use function substr;

class InvalidConstantNode extends RuntimeException
{
    public static function create(Node $node): self
    {
        return new self(sprintf(
            'Invalid constant node (first 50 characters: %s)',
            substr((new Standard())->prettyPrint([$node]), 0, 50),
        ));
    }
}
