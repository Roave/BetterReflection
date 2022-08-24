<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast\Exception;

use PhpParser\Error;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use RuntimeException;
use Throwable;

use function array_slice;
use function count;
use function explode;
use function implode;
use function max;
use function min;
use function sprintf;

class ParseToAstFailure extends RuntimeException
{
    public static function fromLocatedSource(LocatedSource $locatedSource, Throwable $previous): self
    {
        $additionalInformation = '';

        $fileName = $locatedSource->getFileName();

        if ($fileName !== null) {
            $additionalInformation .= sprintf(' in file %s', $fileName);
        }

        if ($previous instanceof Error) {
            $errorStartLine = $previous->getStartLine();

            $source = null;

            if ($errorStartLine !== -1) {
                $additionalInformation .= sprintf(' (line %d)', $errorStartLine);

                $lines = explode("\n", $locatedSource->getSource());

                $minLine = max(1, $errorStartLine - 5);
                $maxLine = min(count($lines), $errorStartLine + 5);

                $source = implode("\n", array_slice($lines, $minLine - 1, $maxLine - $minLine + 1));
            }

            $additionalInformation .= sprintf(': %s', $previous->getRawMessage());

            if ($source !== null) {
                $additionalInformation .= sprintf("\n\n%s", $source);
            }
        } else {
            $additionalInformation .= sprintf(': %s', $previous->getMessage());
        }

        return new self(sprintf(
            'AST failed to parse in located source%s',
            $additionalInformation,
        ), previous: $previous);
    }
}
