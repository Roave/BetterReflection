<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Util\Autoload\Exception;

final class FailedToLoadClass extends \LogicException
{
    public static function fromClassName(string $className) : self
    {
        return new self(\sprintf('Unable to load class %s', $className));
    }
}
