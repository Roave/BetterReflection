<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Exception;

use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinter\Standard;
use RuntimeException;

use function sprintf;
use function substr;

class InvalidArrowFunctionBodyNode extends RuntimeException
{
    public static function create(Stmt $node): self
    {
        return new self(sprintf(
            'Invalid arrow function body node (first 50 characters: %s)',
            substr((new Standard())->prettyPrint([$node]), 0, 50),
        ));
    }
}
