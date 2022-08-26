<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use InvalidArgumentException;
use ReflectionClass;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\Located\EvaledLocatedSource;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\Util\ClassExistenceChecker;

use function is_file;

final class EvaledCodeSourceLocator extends AbstractSourceLocator
{
    public function __construct(Locator $astLocator, private SourceStubber $stubber)
    {
        parent::__construct($astLocator);
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws InvalidFileLocation
     */
    protected function createLocatedSource(Identifier $identifier): LocatedSource|null
    {
        $classReflection = $this->getInternalReflectionClass($identifier);

        if ($classReflection === null) {
            return null;
        }

        $stubData = $this->stubber->generateClassStub($classReflection->getName());

        if ($stubData === null) {
            return null;
        }

        return new EvaledLocatedSource($stubData->getStub(), $classReflection->getName());
    }

    private function getInternalReflectionClass(Identifier $identifier): ReflectionClass|null
    {
        if (! $identifier->isClass()) {
            return null;
        }

        /** @psalm-var class-string|trait-string $name */
        $name = $identifier->getName();

        if (! ClassExistenceChecker::exists($name)) {
            return null; // not an available internal class
        }

        $reflection = new ReflectionClass($name);
        $sourceFile = $reflection->getFileName();

        return $sourceFile && is_file($sourceFile)
            ? null : $reflection;
    }
}
