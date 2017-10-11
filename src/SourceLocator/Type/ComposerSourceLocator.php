<?php
declare(strict_types=1);

namespace Rector\BetterReflection\SourceLocator\Type;

use Composer\Autoload\ClassLoader;
use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Located\LocatedSource;

/**
 * This source locator uses Composer's built-in ClassLoader to locate files.
 *
 * Note that we use ClassLoader->findFile directory, rather than
 * ClassLoader->loadClass because this library has a strict requirement that we
 * do NOT actually load the classes
 */
class ComposerSourceLocator extends AbstractSourceLocator
{
    /**
     * @var ClassLoader
     */
    private $classLoader;

    public function __construct(ClassLoader $classLoader, Locator $astLocator)
    {
        parent::__construct($astLocator);
        $this->classLoader = $classLoader;
    }

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     * @throws \Rector\BetterReflection\SourceLocator\Exception\InvalidFileLocation
     */
    protected function createLocatedSource(Identifier $identifier) : ?LocatedSource
    {
        if (IdentifierType::IDENTIFIER_CLASS !== $identifier->getType()->getName()) {
            return null;
        }

        $filename = $this->classLoader->findFile($identifier->getName());

        if ( ! $filename) {
            return null;
        }

        return new LocatedSource(
            \file_get_contents($filename),
            $filename
        );
    }
}
