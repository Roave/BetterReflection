<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Fixture;

use Attribute;

#[Attribute(Attribute::TARGET_ALL | Attribute::IS_REPEATABLE)]
class AttrWithCallback
{
    public function __construct(public mixed $callback)
    {
    }
}

#[AttrWithCallback(static function (int $value): int {
    return $value * 2;
})]
#[AttrWithCallback(callback: static function (string $value): string {
    return strtolower($value);
})]
#[AttrWithCallback(strtoupper(...))]
class ClassWithClosuresInAttributes
{
    public function methodWithClosureInParameterDefault(
        mixed $callback = static function (): string {
            return 'default';
        },
    ): void {
    }
}

#[AttrWithCallback(static function (): string {
    return self::secret();
})]
class ClassWithScopedClosureInAttribute
{
    private static function secret(): string
    {
        return 'scoped';
    }
}
