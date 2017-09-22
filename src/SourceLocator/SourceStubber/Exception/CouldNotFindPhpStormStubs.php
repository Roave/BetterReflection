<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber\Exception;

use RuntimeException;

class CouldNotFindPhpStormStubs extends RuntimeException
{
    public static function create() : self
    {
        return new self('Could not find PhpStorm stubs');
    }
}
