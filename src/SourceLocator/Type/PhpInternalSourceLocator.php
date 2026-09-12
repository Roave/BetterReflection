<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use InvalidArgumentException;
use ReflectionClass as CoreReflectionClass;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\StubData;

use function class_exists;
use function strtolower;

final class PhpInternalSourceLocator extends AbstractSourceLocator
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
        return $this->getClassSource($identifier)
            ?? $this->getFunctionSource($identifier)
            ?? $this->getConstantSource($identifier);
    }

    private function getClassSource(Identifier $identifier): InternalLocatedSource|null
    {
        if (! $identifier->isClass()) {
            return null;
        }

        /** @psalm-var class-string|trait-string $className */
        $className = $identifier->getName();
        $aliasName = null;

        if (class_exists($className, false)) {
            $reflectionClass = new CoreReflectionClass($className);

            if (strtolower($reflectionClass->getName()) !== strtolower($className)) {
                $aliasName = $className;
                /** @psalm-var class-string|trait-string $className */
                $className  = $reflectionClass->getName();
                $identifier = new Identifier($className, $identifier->getType());
            }
        }

        return $this->createLocatedSourceFromStubData($identifier, $this->stubber->generateClassStub($className), $aliasName);
    }

    private function getFunctionSource(Identifier $identifier): InternalLocatedSource|null
    {
        if (! $identifier->isFunction()) {
            return null;
        }

        return $this->createLocatedSourceFromStubData($identifier, $this->stubber->generateFunctionStub($identifier->getName()));
    }

    private function getConstantSource(Identifier $identifier): InternalLocatedSource|null
    {
        if (! $identifier->isConstant()) {
            return null;
        }

        return $this->createLocatedSourceFromStubData($identifier, $this->stubber->generateConstantStub($identifier->getName()));
    }

    private function createLocatedSourceFromStubData(Identifier $identifier, StubData|null $stubData, string|null $aliasName = null): InternalLocatedSource|null
    {
        if ($stubData === null) {
            return null;
        }

        $extensionName = $stubData->getExtensionName();

        if ($extensionName === null) {
            // Not internal
            return null;
        }

        return new InternalLocatedSource(
            $stubData->getStub(),
            $identifier->getName(),
            $extensionName,
            $aliasName,
        );
    }
}
