<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use InvalidArgumentException;
use ReflectionClass;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Reflection\SourceStubber;
use function class_exists;
use function file_get_contents;
use function interface_exists;
use function is_file;
use function is_readable;
use function preg_match;
use function trait_exists;

final class PhpInternalSourceLocator extends AbstractSourceLocator
{
    /** @var SourceStubber */
    private $stubber;

    public function __construct(Locator $astLocator)
    {
        parent::__construct($astLocator);

        $this->stubber = new SourceStubber();
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws InvalidFileLocation
     */
    protected function createLocatedSource(Identifier $identifier) : ?LocatedSource
    {
        $classReflection = $this->getInternalReflectionClass($identifier);

        if ($classReflection === null) {
            return null;
        }

        $extensionName = $classReflection->getExtensionName();
        $stub          = $this->getStub($classReflection->getName());

        if ($stub) {
            /**
             * @see https://github.com/Roave/BetterReflection/issues/257
             *
             * @todo this code path looks never used, and disagrees with the contract anyway...?
             */
            return new InternalLocatedSource("<?php\n\n" . $stub, $extensionName);
        }

        $stubber = $this->stubber;

        return new InternalLocatedSource(
            "<?php\n\n" . $stubber($classReflection),
            $extensionName
        );
    }

    private function getInternalReflectionClass(Identifier $identifier) : ?ReflectionClass
    {
        if (! $identifier->isClass()) {
            return null;
        }

        $name = $identifier->getName();

        if (! (class_exists($name, false) || interface_exists($name, false) || trait_exists($name, false))) {
            return null; // not an available internal class
        }

        $reflection = new ReflectionClass($name);

        return $reflection->isInternal() ? $reflection : null;
    }

    /**
     * Get the stub source code for an internal class.
     *
     * Returns null if nothing is found.
     *
     * @param string $className Should only contain [A-Za-z]
     */
    private function getStub(string $className) : ?string
    {
        if (! $this->hasStub($className)) {
            return null;
        }

        return file_get_contents($this->buildStubName($className));
    }

    /**
     * Determine the stub name
     */
    private function buildStubName(string $className) : ?string
    {
        if (! preg_match('/^[a-zA-Z_][a-zA-Z_\d]*$/', $className)) {
            return null;
        }

        return __DIR__ . '/../../../stub/' . $className . '.stub';
    }

    /**
     * Determine if a stub exists for specified class name
     */
    public function hasStub(string $className) : bool
    {
        $expectedStubName = $this->buildStubName($className);

        if ($expectedStubName === null) {
            return false;
        }

        return is_file($expectedStubName) && is_readable($expectedStubName);
    }
}
