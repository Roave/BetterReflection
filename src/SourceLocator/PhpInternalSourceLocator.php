<?php

namespace BetterReflection\SourceLocator;

use BetterReflection\Identifier\Identifier;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Reflection\ClassReflection;

class PhpInternalSourceLocator implements SourceLocator
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(Identifier $identifier)
    {
        if (! $name = $this->getInternalReflectionClassName($identifier)) {
            return null;
        }

        return new LocatedSource(
            "<?php\n\n" . ClassGenerator::fromReflection(new ClassReflection($name))->generate(),
            null
        );
    }

    /**
     * @param Identifier $identifier
     *
     * @return null|string
     */
    private function getInternalReflectionClassName(Identifier $identifier)
    {
        if (! $identifier->isClass()) {
            return null;
        }

        $name = $identifier->getName();

        if (! (class_exists($name, false) || interface_exists($name, false) || trait_exists($name, false))) {
            return null; // not an available internal class
        }

        $reflection = new \ReflectionClass($name);

        return $reflection->isInternal() ? $reflection->getName() : null;
    }
}
