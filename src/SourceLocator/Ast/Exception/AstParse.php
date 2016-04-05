<?php

namespace BetterReflection\SourceLocator\Ast\Exception;

use BetterReflection\SourceLocator\Located\LocatedSource;

class AstParse extends \RuntimeException
{
    public static function fromLocatedSource(LocatedSource $locatedSource, \Exception $previous = null)
    {
        $additionalInformation = '';
        if (null !== $locatedSource->getFileName()) {
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
