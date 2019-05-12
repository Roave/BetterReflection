<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber;

use function array_merge;

class AggregateSourceStubber implements SourceStubber
{
    /** @var SourceStubber[] */
    private $sourceStubbers;

    public function __construct(SourceStubber $sourceStubber, SourceStubber ...$otherSourceStubbers)
    {
        $this->sourceStubbers = array_merge([$sourceStubber], $otherSourceStubbers);
    }

    /**
     * {@inheritDoc}
     */
    public function generateClassStub(string $className) : ?StubData
    {
        foreach ($this->sourceStubbers as $sourceStubber) {
            $stubData = $sourceStubber->generateClassStub($className);

            if ($stubData !== null) {
                return $stubData;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function generateFunctionStub(string $functionName) : ?StubData
    {
        foreach ($this->sourceStubbers as $sourceStubber) {
            $stubData = $sourceStubber->generateFunctionStub($functionName);

            if ($stubData !== null) {
                return $stubData;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function generateConstantStub(string $constantName) : ?StubData
    {
        foreach ($this->sourceStubbers as $sourceStubber) {
            $stubData = $sourceStubber->generateConstantStub($constantName);

            if ($stubData !== null) {
                return $stubData;
            }
        }

        return null;
    }
}
