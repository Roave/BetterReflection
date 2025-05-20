<?php

// https://wiki.php.net/rfc/property-hooks

namespace Roave\BetterReflectionTest\Fixture;

use function strtolower;
use function strtoupper;

class PropertyHooks
{
    public string $readOnlyHook {
        get {
            return 'hook';
        }
    }

    public string $writeOnlyHook {
        set (string $value) {
            $this->writeOnlyHook = $value;
        }
    }

    public string $readAndWriteHook {
        get {
            $this->readAndWriteHook;
        }
        set (string $value) {
            $this->readAndWriteHook = $value;
        }
    }
}

abstract class ToBeVirtualOrNotToBeVirtualThatIsTheQuestion
{
    public string $notVirtualBecauseNoHooks = 'string';

    public string $notVirtualBecauseThePropertyIsUsedInGet {
        get {
            return strtoupper($this->notVirtualBecauseThePropertyIsUsedInGet);
        }
    }

    public string $notVirtualBecauseOfShortSyntax {
        set => strtolower($value);
    }

    public string $virtualBecauseThePropertyIsNotUsedInGet {
        get => 'value';
    }

    public string $virtualBecauseSetWorksWithDifferentProperty {
        set (string $value) {
            $this->differentProperty = $value;
        }
    }

    public string $notVirtualBecauseIsPublicSoTheSetWithDifferentPropertyIsNotRelevant {
        set {
            $this->differentProperty = $value;
        }
        get {
            return $this->notVirtualBecauseIsPublicSoTheSetWithDifferentPropertyIsNotRelevant;
        }
    }

    abstract public string $virtualBecauseGetAndSetAbstract {
        get;
        set;
    }

    abstract public string $notVirtualBecauseSetIsNotAbstract {
        get;
        set (string $value) {
            $this->notVirtualBecauseSetIsNotAbstract = $value;
        }
    }
}

abstract class AbstractPropertyHooks
{
    abstract public string $hook { get; }
}

class GetPropertyHook extends AbstractPropertyHooks
{
    public string $hook {
        get {
            return 'hook';
        }
    }
}

class GetAndSetPropertyHook extends GetPropertyHook
{
    public string $hook {
        set (string $value) {
            $this->hook = $value;
        }
    }
}

class SetPropertyHook
{
    public string $hook {
        set (string $value) {
            $this->hook = $value;
        }
    }
}

class SetAndGetPropertyHook extends SetPropertyHook
{
    public string $hook {
        get {
            return 'hook';
        }
    }
}

class BothPropertyHooks
{
    public string $hook {
        get {
            return 'hook';
        }
        set (string $value) {
            $this->hook = $value;
        }
    }
}

class ExtendedHooks extends BothPropertyHooks
{
    public string $hook {
        get {
            return 'hook2';
        }
    }
}

trait PropertyHookTrait
{
    public string $hook {
        get {
            return 'hook';
        }
    }
}

class UsePropertyHookFromTrait
{
    use PropertyHookTrait;
}

interface InterfacePropertyHooks
{
    public string $readOnlyHook { get; }

    public string $writeOnlyHook { set; }

    public string $readAndWriteHook { get; set; }
}

class PromotedPropertyHooks
{
    public function __construct(string $hook{ get {} })
    {
    }
}

class SetPropertyHooksParameters
{
    public string $hookWithImplicitParameter { set => strtolower($value); }

    public int $hookWithExplicitParameter {
        set (int $value) {
            $this->hookWithExplicitParameter = $value;
        }
     }

}

class GetPropertyHooksReturnTypes
{
    public string $hookWithString { get => 'string'; }

    public int $hookWithInteger { get => 42; }

    public string|int $hookWithUnion { get => 'string'; }

    public $hookWithoutType { get => 'string'; }

}

class FinalPropertyHooks
{
    public string $notFinalHook {
        set => strtolower($value);
    }

    public string $finalHook {
        final set => strtolower($value);
    }
}

interface InterfaceWithProperty
{
    public int $abstractPropertyFromInterface { get; set; }
}
