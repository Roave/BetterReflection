<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Psr;

use Roave\BetterReflection\Identifier\Identifier;

interface PsrAutoloaderMapping
{
    /** @return list<string> */
    public function resolvePossibleFilePaths(Identifier $identifier) : array;

    /** @return list<string> */
    public function directories() : array;
}
