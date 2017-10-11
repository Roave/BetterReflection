<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Reflector;

use Rector\BetterReflection\Reflection\Reflection;
use Rector\BetterReflection\Reflector\Exception\IdentifierNotFound;

/**
 * This interface is used to ensure a reflector implements these basic methods.
 */
interface Reflector
{
    /**
     * Create a reflection from the named identifier.
     *
     * @param string $identifierName
     * @return Reflection
     * @throws IdentifierNotFound
     */
    public function reflect(string $identifierName) : Reflection;
}
