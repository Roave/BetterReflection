<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Exception;

class EvaledClosureCannotBeLocated extends \LogicException
{
    public static function create() : self
    {
        return new self('Evaled closure cannot be located');
    }
}
