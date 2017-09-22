<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber;

use ReflectionClass as CoreReflectionClass;
use ReflectionFunction as CoreReflectionFunction;
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
    public function generateClassStub(CoreReflectionClass $classReflection) : ?string
    {
        foreach ($this->sourceStubbers as $sourceStubber) {
            $stub = $sourceStubber->generateClassStub($classReflection);

            if ($stub !== null) {
                return $stub;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function generateFunctionStub(CoreReflectionFunction $functionReflection) : ?string
    {
        foreach ($this->sourceStubbers as $sourceStubber) {
            $stub = $sourceStubber->generateFunctionStub($functionReflection);

            if ($stub !== null) {
                return $stub;
            }
        }

        return null;
    }
}
