<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Exception;

use RuntimeException;

/**
 * This is removed in PR #236 - this should never have existed, but leaving here for BC. This exception is never thrown
 * so you should remove it from your code.
 *
 * You were probably looking to catch \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound instead.
 *
 * @deprecated You're probably looking for `IdentifierNotFound`
 *
 * @see \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound
 */
class FunctionUndefined extends RuntimeException
{
}
