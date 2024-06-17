<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Exception;

use RuntimeException;

class CodeLocationMissing extends RuntimeException
{
    public static function create(string|null $hint = null): self
    {
        $message = 'Code location is missing';
        if ($hint !== null) {
            $message .= '. ' . $hint;
        }

        return new self($message);
    }
}
