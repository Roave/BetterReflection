<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Identifier;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Identifier\Exception\InvalidIdentifierName;
use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Reflection\ReflectionFunctionAbstract;

/**
 * @covers \Rector\BetterReflection\Identifier\Identifier
 */
class IdentifierTest extends TestCase
{
    public function testGetName() : void
    {
        $beforeName = '\Some\Thing\Here';
        $afterName  = 'Some\Thing\Here';

        $identifier = new Identifier($beforeName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
        self::assertSame($afterName, $identifier->getName());
    }

    public function testGetType() : void
    {
        $identifierType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        $identifier = new Identifier('Foo', $identifierType);
        self::assertSame($identifierType, $identifier->getType());
    }

    public function testIsTypesForClass() : void
    {
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        self::assertTrue($identifier->isClass());
        self::assertFalse($identifier->isFunction());
    }

    public function testIsTypesForFunction() : void
    {
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));

        self::assertFalse($identifier->isClass());
        self::assertTrue($identifier->isFunction());
    }

    public function testGetNameForClosure() : void
    {
        $identifier = new Identifier(ReflectionFunctionAbstract::CLOSURE_NAME, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));
        self::assertSame(ReflectionFunctionAbstract::CLOSURE_NAME, $identifier->getName());
    }

    public function testGetNameForAnonymousClass() : void
    {
        $identifier = new Identifier(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX . ' filename.php', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
        self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $identifier->getName());
    }

    public function testGetNameForWildcard() : void
    {
        $identifier = new Identifier(Identifier::WILDCARD, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
        self::assertSame(Identifier::WILDCARD, $identifier->getName());
    }

    public function validNamesProvider() : array
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

    /**
     * @param string $name
     * @param string $expectedName
     * @dataProvider validNamesProvider
     */
    public function testValidName(string $name, string $expectedName) : void
    {
        $identifier = new Identifier($name, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
        self::assertSame($expectedName, $identifier->getName());
    }

    public function invalidNamesProvider() : array
    {
        return [
            [''],
            ['1234567890'],
            ['!@#$%^&*()'],
            ['\\'],
        ];
    }

    /**
     * @param string $invalidName
     * @dataProvider invalidNamesProvider
     */
    public function testThrowExceptionForInvalidName(string $invalidName) : void
    {
        $this->expectException(InvalidIdentifierName::class);
        new Identifier($invalidName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
    }
}
