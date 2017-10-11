<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Util\Exception;

use InvalidArgumentException;

class InvalidNodePosition extends InvalidArgumentException
{
    public static function fromPosition(int $position) : self
    {
        return new self(\sprintf('Invalid position %d', $position));
    }
}
