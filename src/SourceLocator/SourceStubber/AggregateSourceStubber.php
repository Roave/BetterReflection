<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber;

use function array_merge;
use function array_reduce;
use function array_values;

class AggregateSourceStubber implements SourceStubber
{
    /** @var list<SourceStubber> */
    private array $sourceStubbers;

    public function __construct(SourceStubber $sourceStubber, SourceStubber ...$otherSourceStubbers)
    {
        $this->sourceStubbers = array_values(array_merge([$sourceStubber], $otherSourceStubbers));
    }

    /** @param class-string|trait-string $className */
    public function generateClassStub(string $className): StubData|null
    {
        foreach ($this->sourceStubbers as $sourceStubber) {
            $stubData = $sourceStubber->generateClassStub($className);

            if ($stubData !== null) {
                return $stubData;
            }
        }

        return null;
    }

    public function generateFunctionStub(string $functionName): StubData|null
    {
        foreach ($this->sourceStubbers as $sourceStubber) {
            $stubData = $sourceStubber->generateFunctionStub($functionName);

            if ($stubData !== null) {
                return $stubData;
            }
        }

        return null;
    }

    public function generateConstantStub(string $constantName): StubData|null
    {
        return array_reduce($this->sourceStubbers, static fn (StubData|null $stubData, SourceStubber $sourceStubber): StubData|null => $stubData ?? $sourceStubber->generateConstantStub($constantName), null);
    }
}
