<?php

namespace Roave\BetterReflection\SourceLocator\Exception;

use SuperClosure\Exception\ClosureAnalysisException;

class TwoClosuresOneLine extends \LogicException
{
    /**
     * Wrap it up
     *
     * @param ClosureAnalysisException $closureAnalysisException
     * @return TwoClosuresOneLine
     */
    public static function fromClosureAnalysisException(ClosureAnalysisException $closureAnalysisException) : self
    {
        return new self($closureAnalysisException->getMessage(), $closureAnalysisException->getCode(), $closureAnalysisException);
    }
}
