<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use PhpParser\Parser;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Reflection\SourceStubber;
use Zend\Code\Reflection\ClassReflection;

final class PhpInternalSourceLocator extends AbstractSourceLocator
{
    /**
     * @var SourceStubber
     */
    private $stubber;

    public function __construct(?Locator $locator = null)
    {
        parent::__construct($locator);
        $this->stubber = new SourceStubber();
    }

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     * @throws \Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation
     */
    protected function createLocatedSource(Identifier $identifier) : ?LocatedSource
    {
        if (! $name = $this->getInternalReflectionClassName($identifier)) {
            return null;
        }

        if ($stub = $this->getStub($name)) {
            /**
             * @todo this code path looks never used, and disagrees with the contract anyway...?
             * @see https://github.com/Roave/BetterReflection/issues/257
             */
            return [
                "<?php\n\n" . $stub,
            ];
        }

        $stubber = $this->stubber;

        return new InternalLocatedSource(
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
        if (! $identifier->isClass()) {
            return null;
        }

        $name = $identifier->getName();

        if (! (class_exists($name, false) || interface_exists($name, false) || trait_exists($name, false))) {
            return null; // not an available internal class
        }

        $reflection = new \ReflectionClass($name);

        return $reflection->isInternal() ? $reflection->getName() : null;
    }

    /**
     * Get the stub source code for an internal class.
     *
     * Returns null if nothing is found.
     *
     * @param string $className Should only contain [A-Za-z]
     * @return string|null
     */
    private function getStub(string $className) : ?string
    {
        if (!$this->hasStub($className)) {
            return null;
        }

        return file_get_contents($this->buildStubName($className));
    }

    /**
     * Determine the stub name
     *
     * @param string $className
     * @return string|null
     */
    private function buildStubName(string $className) : ?string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z_\d]*$/', $className)) {
            return null;
        }

        return __DIR__ . '/../../stub/' . $className . '.stub';
    }

    /**
     * Determine if a stub exists for specified class name
     *
     * @param string $className
     * @return bool
     */
    public function hasStub(string $className) : bool
    {
        $expectedStubName = $this->buildStubName($className);

        if (null === $expectedStubName) {
            return false;
        }

        if (!file_exists($expectedStubName) || !is_readable($expectedStubName) || !is_file($expectedStubName)) {
            return false;
        }

        return true;
    }
}
