<?php

namespace BetterReflection\SourceLocator;

use BetterReflection\Identifier\Identifier;
use Zend\Code\Reflection\ClassReflection;

final class PhpInternalSourceLocator implements SourceLocator
{
    /**
     * @var SourceStubber
     */
    private $stubber;

    public function __construct()
    {
        $this->stubber = new SourceStubber();
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(Identifier $identifier)
    {
        if (! $name = $this->getInternalReflectionClassName($identifier)) {
            return null;
        }

        if ($stub = $this->getStub($name)) {
            return new InternalLocatedSource("<?php\n\n" . $stub);
        }

        $stubber = $this->stubber;

        return new InternalLocatedSource("<?php\n\n" . $stubber(new ClassReflection($name)));
    }

    /**
     * @param Identifier $identifier
     *
     * @return null|string
     */
    private function getInternalReflectionClassName(Identifier $identifier)
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
    private function getStub($className)
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
    private function buildStubName($className)
    {
        if (!preg_match('/^[a-zA-Z]+$/', $className)) {
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
    public function hasStub($className)
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
