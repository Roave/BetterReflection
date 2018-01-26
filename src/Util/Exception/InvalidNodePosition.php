<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util\Exception;

use InvalidArgumentException;
use function sprintf;

class InvalidNodePosition extends InvalidArgumentException
{
    public static function fromPosition(int $position) : self
    {
        return new self(sprintf('Invalid position %d', $position));
    }
}
