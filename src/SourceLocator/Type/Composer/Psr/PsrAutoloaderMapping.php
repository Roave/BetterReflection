<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Psr;

use Roave\BetterReflection\Identifier\Identifier;

interface PsrAutoloaderMapping
{
    /** @psalm-return list<string> */
    public function resolvePossibleFilePaths(Identifier $identifier) : array;

    /** @psalm-return list<string> */
    public function directories() : array;
}
