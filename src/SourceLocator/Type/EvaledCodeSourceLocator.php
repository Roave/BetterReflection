<?php

namespace BetterReflection\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\SourceLocator\Located\EvaledLocatedSource;
use BetterReflection\SourceLocator\Reflection\SourceStubber;
use Zend\Code\Reflection\ClassReflection;

final class EvaledCodeSourceLocator implements SourceLocator
{
    /**
     * @var SourceStubber
     */
    private $stubber;

    public function __construct()
    {
        $this->stubber = new SourceStubber();
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(Identifier $identifier)
    {
        if (! $name = $this->getInternalReflectionClassName($identifier)) {
            return null;
        }

        $stubber = $this->stubber;

        return new EvaledLocatedSource("<?php\n\n" . $stubber(new ClassReflection($name)));
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
        $sourceFile = $reflection->getFileName();

        return ($sourceFile && file_exists($sourceFile))
            ? null : $reflection->getName();
    }
}
