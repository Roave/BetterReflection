<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Located;

use InvalidArgumentException;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\Util\FileHelper;

use function assert;

/**
 * Value object containing source code that has been located.
 *
 * @internal
 *
 * @psalm-immutable
 */
class LocatedSource
{
    /** @var non-empty-string|null */
    private string|null $filename;

    /**
     * @throws InvalidArgumentException
     * @throws InvalidFileLocation
     */
    public function __construct(private string $source, private string|null $name, string|null $filename = null)
    {
        if ($filename !== null) {
            assert($filename !== '');

            $filename = FileHelper::normalizeWindowsPath($filename);
        }

        $this->filename = $filename;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    /** @return non-empty-string|null */
    public function getFileName(): string|null
    {
        return $this->filename;
    }

    /**
     * Is the located source in PHP internals?
     */
    public function isInternal(): bool
    {
        return false;
    }

    /** @return non-empty-string|null */
    public function getExtensionName(): string|null
    {
        return null;
    }

    /**
     * Is the located source produced by eval() or \function_create()?
     */
    public function isEvaled(): bool
    {
        return false;
    }

    public function getAliasName(): string|null
    {
        return null;
    }
}
