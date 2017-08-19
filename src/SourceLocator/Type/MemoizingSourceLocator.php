<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;

final class MemoizingSourceLocator implements SourceLocator
{
    /**
     * @var SourceLocator
     */
    private $wrappedSourceLocator;

    /**
     * @var Reflection[][] indexed by reflector key and identifier cache key
     */
    private $cacheByIdentifierKeyAndOid = [];

    /**
     * @var Reflection[][][] indexed by reflector key and identifier type cache key
     */
    private $cacheByIdentifierTypeKeyAndOid = [];

    public function __construct(SourceLocator $wrappedSourceLocator)
    {
        $this->wrappedSourceLocator = $wrappedSourceLocator;
    }

    public function locateIdentifier(Reflector $reflector, Identifier $identifier) : ?Reflection
    {
        $cacheKey     = $this->identifierToCacheKey($identifier);
        $reflectorKey = $this->reflectorCacheKey($reflector);

        if (! isset($this->cacheByIdentifierKeyAndOid[$reflectorKey])) {
            $this->cacheByIdentifierKeyAndOid[$reflectorKey] = [];
        }

        if (\array_key_exists($cacheKey, $this->cacheByIdentifierKeyAndOid[$reflectorKey])) {
            return $this->cacheByIdentifierKeyAndOid[$reflectorKey][$cacheKey];
        }

        return $this->cacheByIdentifierKeyAndOid[$reflectorKey][$cacheKey]
            = $this->wrappedSourceLocator->locateIdentifier($reflector, $identifier);
    }

    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType) : array
    {
        $cacheKey     = $this->identifierTypeToCacheKey($identifierType);
        $reflectorKey = $this->reflectorCacheKey($reflector);

        if (! isset($this->cacheByIdentifierTypeKeyAndOid[$reflectorKey])) {
            $this->cacheByIdentifierTypeKeyAndOid[$reflectorKey] = [];
        }

        if (\array_key_exists($cacheKey, $this->cacheByIdentifierTypeKeyAndOid[$reflectorKey])) {
            return $this->cacheByIdentifierTypeKeyAndOid[$reflectorKey][$cacheKey];
        }

        return $this->cacheByIdentifierTypeKeyAndOid[$reflectorKey][$cacheKey]
            = $this->wrappedSourceLocator->locateIdentifiersByType($reflector, $identifierType);
    }

    private function reflectorCacheKey(Reflector $reflector) : string
    {
        return 'type:' . \get_class($reflector)
            . '#oid:' . \spl_object_hash($reflector);
    }

    private function identifierToCacheKey(Identifier $identifier) : string
    {
        return $this->identifierTypeToCacheKey($identifier->getType())
            . '#name:' . $identifier->getName();
    }

    private function identifierTypeToCacheKey(IdentifierType $identifierType) : string
    {
        return 'type:' . $identifierType->getName();
    }
}
