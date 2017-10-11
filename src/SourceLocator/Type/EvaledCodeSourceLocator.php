<?php
declare(strict_types=1);

namespace Rector\BetterReflection\SourceLocator\Type;

use ReflectionClass;
use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Located\EvaledLocatedSource;
use Rector\BetterReflection\SourceLocator\Located\LocatedSource;
use Rector\BetterReflection\SourceLocator\Reflection\SourceStubber;

final class EvaledCodeSourceLocator extends AbstractSourceLocator
{
    /**
     * @var SourceStubber
     */
    private $stubber;

    public function __construct(Locator $astLocator)
    {
        parent::__construct($astLocator);

        $this->stubber = new SourceStubber();
    }

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     * @throws \Rector\BetterReflection\SourceLocator\Exception\InvalidFileLocation
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
