<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use ReflectionClass;
use Roave\BetterReflection\Configuration;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Located\EvaledLocatedSource;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Reflection\SourceStubber;

final class EvaledCodeSourceLocator extends AbstractSourceLocator
{
    /**
     * @var SourceStubber
     */
    private $stubber;

    public function __construct()
    {
        parent::__construct();

        $this->stubber = new SourceStubber((new Configuration())->phpParser()); // @TODO inject parser here
    }

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     * @throws \Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation
     */
    protected function createLocatedSource(Identifier $identifier) : ?LocatedSource
    {
        $classReflection = $this->getInternalReflectionClass($identifier);

        if (null === $classReflection) {
            return null;
        }

        $stubber = $this->stubber;

        return new EvaledLocatedSource(
            "<?php\n\n" . $stubber($classReflection)
        );
    }

    private function getInternalReflectionClass(Identifier $identifier) : ?ReflectionClass
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
            ? null : $reflection;
    }
}
