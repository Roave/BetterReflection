<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Identifier;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Identifier\Exception\InvalidIdentifierName;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;

#[CoversClass(Identifier::class)]
class IdentifierTest extends TestCase
{
    public function testGetName(): void
    {
        $beforeName = '\Some\Thing\Here';
        $afterName  = 'Some\Thing\Here';

        $identifier = new Identifier($beforeName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
        self::assertSame($afterName, $identifier->getName());
    }

    public function testGetType(): void
    {
        $identifierType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        $identifier = new Identifier('Foo', $identifierType);
        self::assertSame($identifierType, $identifier->getType());
    }

    public function testIsTypesForClass(): void
    {
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        self::assertTrue($identifier->isClass());
        self::assertFalse($identifier->isFunction());
        self::assertFalse($identifier->isConstant());
    }

    public function testIsTypesForFunction(): void
    {
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));

        self::assertFalse($identifier->isClass());
        self::assertTrue($identifier->isFunction());
        self::assertFalse($identifier->isConstant());
    }

    public function testIsTypesForConstant(): void
    {
        $identifier = new Identifier('FOO', new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT));

        self::assertFalse($identifier->isClass());
        self::assertFalse($identifier->isFunction());
        self::assertTrue($identifier->isConstant());
    }

    public function testGetNameForClosure(): void
    {
        $identifier = new Identifier(ReflectionFunction::CLOSURE_NAME, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));
        self::assertSame(ReflectionFunction::CLOSURE_NAME, $identifier->getName());
    }

    public function testGetNameForAnonymousClass(): void
    {
        $identifier = new Identifier(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX . ' filename.php', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
        self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $identifier->getName());
    }

    public function testGetNameForWildcard(): void
    {
        $identifier = new Identifier(Identifier::WILDCARD, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
        self::assertSame(Identifier::WILDCARD, $identifier->getName());
    }

    /** @return list<array{0: string, 1: string}> */
    public static function validNamesProvider(): array
    {
        return [
            ['Foo', 'Foo'],
            ['\Foo', 'Foo'],
            ['Foo\Bar', 'Foo\Bar'],
            ['\Foo\Bar', 'Foo\Bar'],
            ['F', 'F'],
            ['F\B', 'F\B'],
            ['foo', 'foo'],
            ['\foo', 'foo'],
            ['Foo\bar', 'Foo\bar'],
            ['\Foo\bar', 'Foo\bar'],
            ['f', 'f'],
            ['F\b', 'F\b'],
            ['fooööö', 'fooööö'],
            ['Option«T»', 'Option«T»'],
        ];
    }

    #[DataProvider('validNamesProvider')]
    public function testValidName(string $name, string $expectedName): void
    {
        $identifier = new Identifier($name, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
        self::assertSame($expectedName, $identifier->getName());
    }

    /** @return list<array{0: string}> */
    public static function invalidNamesProvider(): array
    {
        return [
            [''],
            ['1234567890'],
            ['!@#$%^&*()'],
            ['\\'],
        ];
    }

    #[DataProvider('invalidNamesProvider')]
    public function testThrowExceptionForInvalidName(string $invalidName): void
    {
        $this->expectException(InvalidIdentifierName::class);
        new Identifier($invalidName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
    }
}
