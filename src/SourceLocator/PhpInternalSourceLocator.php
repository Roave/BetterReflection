<?php

namespace BetterReflection\SourceLocator;

use BetterReflection\Identifier\Identifier;

class PhpInternalSourceLocator implements SourceLocator
{
    public function __invoke(Identifier $identifier)
    {
        if (!$identifier->isClass()) {
            return null;
        }

        $methodName = str_replace('\\', '__', $identifier->getName());
        if (!method_exists($this, $methodName)) {
            return null;
        }

        return new LocatedSource(
            '<?php ' . $this->$methodName(),
            LocatedSource::INTERNAL_SOURCE_MAGIC_CONST
        );
    }

    public function stdClass()
    {
        return 'class stdClass {}';
    }
}
