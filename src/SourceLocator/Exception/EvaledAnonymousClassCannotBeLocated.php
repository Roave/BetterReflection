<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Exception;

class EvaledAnonymousClassCannotBeLocated extends \LogicException
{
    public static function create() : self
    {
        return new self('Evaled anonymous class cannot be located');
    }
}
