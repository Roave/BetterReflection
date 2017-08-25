<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use ReflectionClass;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Located\EvaledLocatedSource;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Reflection\SourceStubber;
use Zend\Code\Reflection\ClassReflection;

final class EvaledCodeSourceLocator extends AbstractSourceLocator
{
    /**
     * @var SourceStubber
     */
    private $stubber;

    public function __construct()
    {
        parent::__construct();
        $this->stubber = new SourceStubber();
    }

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     * @throws \Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation
     */
    protected function createLocatedSource(Identifier $identifier) : ?LocatedSource
    {
        if ( ! $name = $this->getInternalReflectionClassName($identifier)) {
            return null;
        }

        $stubber = $this->stubber;

        return new EvaledLocatedSource(
            "<?php\n\n" . $stubber(new ClassReflection($name))
        );
    }

    /**
     * @param Identifier $identifier
     *
     * @return null|string
     */
    private function getInternalReflectionClassName(Identifier $identifier) : ?string
    {
        if ( ! $identifier->isClass()) {
            return null;
        }

        $name = $identifier->getName();

        if ( ! (\class_exists($name, false) || \interface_exists($name, false) || \trait_exists($name, false))) {
            return null; // not an available internal class
        }

        $reflection = new ReflectionClass($name);
        $sourceFile = $reflection->getFileName();

        return ($sourceFile && \file_exists($sourceFile))
            ? null : $reflection->getName();
    }
}
