<?php

namespace BetterReflection\SourceLocator;

use BetterReflection\Identifier\Identifier;

class PhpInternalSourceLocator implements SourceLocator
{
    public function __invoke(Identifier $identifier)
    {
        if (!$identifier->isClass()) {
            throw new \LogicException(__CLASS__ . ' can only be used to locate classes');
        }

        $methodName = str_replace('\\', '__', $identifier->getName());
        if (!method_exists($this, $methodName)) {
            throw new Exception\NotInternalClass(sprintf(
                '%s was not a defined internal class, or stub was not found',
                $identifier->getName()
            ));
        }

        return new LocatedSource($this->$methodName(), null);
    }

    public function stdClass()
    {
        return '<?php class stdClass {}';
    }
}
