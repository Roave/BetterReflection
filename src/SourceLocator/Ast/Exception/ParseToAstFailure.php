<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast\Exception;

use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use RuntimeException;
use Throwable;
use function sprintf;
use function substr;

class ParseToAstFailure extends RuntimeException
{
    public static function fromLocatedSource(LocatedSource $locatedSource, Throwable $previous) : self
    {
        $additionalInformation = '';
        if ($locatedSource->getFileName() !== null) {
            $additionalInformation = sprintf(' (in %s)', $locatedSource->getFileName());
        }

        if ($additionalInformation === '') {
            $additionalInformation = sprintf(' (first 20 characters: %s)', substr($locatedSource->getSource(), 0, 20));
        }

        return new self(sprintf(
            'AST failed to parse in located source%s',
            $additionalInformation
        ), 0, $previous);
    }
}
