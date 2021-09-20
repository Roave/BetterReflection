<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\NodeCompiler;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Name;
use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\ConstantReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflection\Util\FileHelper;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\MagicConstantsClass;
use Roave\BetterReflectionTest\Fixture\MagicConstantsTrait;

use function define;
use function realpath;
use function sprintf;
use function uniqid;

use const PHP_EOL;
use const PHP_INT_MAX;

/**
 * @covers \Roave\BetterReflection\NodeCompiler\CompileNodeToValue
 */
class CompileNodeToValueTest extends TestCase
{
    private Parser $parser;

    private Locator $astLocator;

    private ClassReflector $classReflector;

    protected function setUp(): void
    {
        parent::setUp();

        $configuration        = BetterReflectionSingleton::instance();
        $this->parser         = $configuration->phpParser();
        $this->astLocator     = $configuration->astLocator();
        $this->classReflector = $configuration->classReflector();
    }

    private function parseCode(string $phpCode): Node
    {
        return $this->parser->parse('<?php ' . $phpCode . ';')[0];
    }

    private function getDummyContext(): CompilerContext
    {
        return new CompilerContext(new ClassReflector(new StringSourceLocator('<?php', $this->astLocator)), null, null, null, null);
    }

    private function getDummyContextWithEmptyClass(): CompilerContext
    {
        return new CompilerContext(
            new ClassReflector(new StringSourceLocator('<?php class EmptyClass {}', $this->astLocator)),
            null,
            null,
            null,
            null,
        );
    }

    public function nodeProvider(): array
    {
        return [
            ['1', 1],
            ['"hello"', 'hello'],
            ['null', null],
            ['1.1', 1.1],
            ['[]', []],
            ['false', false],
            ['true', true],
            ['[1,2,3]', [1, 2, 3]],
            ['["foo","bar"]', ['foo', 'bar']],
            ['[1 => "foo", 2 => "bar"]', [1 => 'foo', 2 => 'bar']],
            ['["foo" => "bar"]', ['foo' => 'bar']],
            ['-1', -1],
            ['-123.456', -123.456],
            ['2 * 3', 6],
            ['2 + 2 * 3', 8],
            ['2 + (2 * 3)', 8],
            ['(2 + 2) * 3', 12],
            ['5 - 2', 3],
            ['8 / 2', 4],
            ['["foo"."bar" => 2 * 3]', ['foobar' => 6]],
            ['true && false', false],
            ['false && true', false],
            ['false && false', false],
            ['true && true', true],
            ['true || false', true],
            ['false || true', true],
            ['false || false', false],
            ['true || true', true],
            ['0 & 2', 0],
            ['1 & 2', 0],
            ['2 & 2', 2],
            ['3 & 2', 2],
            ['4 & 2', 0],
            ['0 | 2', 2],
            ['1 | 2', 3],
            ['2 | 2', 2],
            ['3 | 2', 3],
            ['4 | 2', 6],
            ['0 ^ 2', 2],
            ['1 ^ 2', 3],
            ['2 ^ 2', 0],
            ['3 ^ 2', 1],
            ['4 ^ 2', 6],
            ['1 == 2', false],
            ['2 == 2', true],
            ['1 == "1"', true],
            ['1 > 2', false],
            ['2 > 2', false],
            ['3 > 2', true],
            ['1 >= 2', false],
            ['2 >= 2', true],
            ['3 >= 2', true],
            ['1 === 2', false],
            ['2 === 2', true],
            ['1 === "1"', false],
            ['true and false', false],
            ['false and true', false],
            ['false and false', false],
            ['true and true', true],
            ['true or false', true],
            ['false or true', true],
            ['false or false', false],
            ['true or true', true],
            ['true xor false', true],
            ['false xor true', true],
            ['false xor false', false],
            ['true xor true', false],
            ['2 % 2', 0],
            ['2 % 4', 2],
            ['1 != 2', true],
            ['2 != 2', false],
            ['1 != "1"', false],
            ['1 !== 2', true],
            ['2 !== 2', false],
            ['1 !== "1"', true],
            ['4 ** 3', 64],
            ['1 << 1', 2],
            ['1 << 2', 4],
            ['1 << 3', 8],
            ['2 >> 1', 1],
            ['4 >> 2', 1],
            ['8 >> 3', 1],
            ['1 < 2', true],
            ['2 < 2', false],
            ['3 < 2', false],
            ['1 <= 2', true],
            ['2 <= 2', true],
            ['3 <= 2', false],
            ['PHP_INT_MAX', PHP_INT_MAX],
            ['PHP_EOL', PHP_EOL],
            ['1 <=> 4', -1],
            ['4 <=> 1', 1],
            ['1 <=> 1', 0],
            ['5 ?? 4', 5],
        ];
    }

    /**
     * @dataProvider nodeProvider
     */
    public function testVariousNodeCompilations(string $phpCode, mixed $expectedValue): void
    {
        $node = $this->parseCode($phpCode);

        $actualValue = (new CompileNodeToValue())->__invoke($node, $this->getDummyContext());

        self::assertSame($expectedValue, $actualValue);
    }

    public function testExceptionThrownWhenInvalidNodeGiven(): void
    {
        $this->expectException(UnableToCompileNode::class);
        $this->expectExceptionMessage(sprintf(
            'Unable to compile expression in unknown context: unrecognized node type %s in file "" (line -1)',
            Yield_::class,
        ));

        (new CompileNodeToValue())->__invoke(new Yield_(), $this->getDummyContext());
    }

    public function testExceptionThrownWhenUndefinedConstUsed(): void
    {
        $this->expectException(UnableToCompileNode::class);
        $this->expectExceptionMessage('Could not locate constant "FOO" while evaluating expression in unknown context in file "" (line -1)');

        (new CompileNodeToValue())->__invoke(new ConstFetch(new Name('FOO')), $this->getDummyContext());
    }

    public function testExceptionThrownWhenUndefinedClassConstUsed(): void
    {
        $this->expectException(UnableToCompileNode::class);
        $this->expectExceptionMessage('Could not locate constant EmptyClass::FOO while trying to evaluate constant expression in unknown context in file "" (line -1)');

        (new CompileNodeToValue())
            ->__invoke(
                new Node\Expr\ClassConstFetch(
                    new Name\FullyQualified('EmptyClass'),
                    new Node\Identifier('FOO'),
                ),
                $this->getDummyContextWithEmptyClass(),
            );
    }

    public function testConstantValueCompiled(): void
    {
        $constName = uniqid('BETTER_REFLECTION_TEST_CONST_', true);
        define($constName, 123);

        self::assertSame(
            123,
            (new CompileNodeToValue())->__invoke(
                new ConstFetch(new Name($constName)),
                $this->getDummyContext(),
            ),
        );
    }

    public function testClassConstantResolutionSelfForMethod(): void
    {
        $phpCode = '<?php
        class Foo {
            const BAR = "baz";
            public function method($param = self::BAR) {}
        }
        ';

        $reflector  = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo  = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('method');
        $paramInfo  = $methodInfo->getParameter('param');

        self::assertSame('baz', $paramInfo->getDefaultValue());
    }

    public function testClassConstantResolutionWithAnotherClassConstant(): void
    {
        $phpCode = <<<'PHP'
<?php
namespace Bar;

class Foo {
    const SECOND = 1;
    const MINUTE = 60 * self::SECOND;
    const HOUR = 60 * self::MINUTE;
    const DAY = 24 * self::HOUR;
    const WEEK = 7 * self::DAY;
}
PHP;

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Bar\Foo');

        self::assertSame(1, $classInfo->getConstant('SECOND')->getValue());
        self::assertSame(60, $classInfo->getConstant('MINUTE')->getValue());
        self::assertSame(3600, $classInfo->getConstant('HOUR')->getValue());
        self::assertSame(86400, $classInfo->getConstant('DAY')->getValue());
        self::assertSame(604800, $classInfo->getConstant('WEEK')->getValue());
    }

    public function testClassConstantResolutionExternalForMethod(): void
    {
        $phpCode = '<?php
        class Foo {
            const BAR = "baz";
        }
        class Bat {
            const QUX = "quux";
            public function method($param = Foo::BAR) {}
        }
        ';

        $reflector  = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo  = $reflector->reflect('Bat');
        $methodInfo = $classInfo->getMethod('method');
        $paramInfo  = $methodInfo->getParameter('param');

        self::assertSame('baz', $paramInfo->getDefaultValue());
    }

    public function testClassConstantResolutionStaticForMethod(): void
    {
        $phpCode = '<?php
        class Foo {
            const BAR = "baz";
            public function method($param = static::BAR) {}
        }
        ';

        $reflector  = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo  = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('method');
        $paramInfo  = $methodInfo->getParameter('param');

        self::assertSame('baz', $paramInfo->getDefaultValue());
    }

    public function testClassConstantClassNameResolution(): void
    {
        $phpCode = '<?php

        class Foo {
        }
        class Bat {
            const QUX = Foo::class;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Bat');
        self::assertSame('Foo', $classInfo->getConstantValue('QUX'));
    }

    public function testClassConstantClassNameNamespaceResolution(): void
    {
        $phpCode = '<?php
        namespace Bar;

        class Foo {
        }
        class Bat {
            const QUX = Foo::class;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Bar\Bat');
        self::assertSame('Bar\Foo', $classInfo->getConstantValue('QUX'));
    }

    public function testClassConstantClassNameOutOfScopeResolution(): void
    {
        $phpCode = '<?php
        namespace Bar;

        use My\Awesome\Foo;

        class Bat {
            const QUX = Foo::class;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Bar\Bat');
        self::assertSame('My\Awesome\Foo', $classInfo->getConstantValue('QUX'));
    }

    public function testClassConstantClassNameAliasedResolution(): void
    {
        $phpCode = '<?php
        namespace Bar;

        use My\Awesome\Foo as FooAlias;

        class Bat {
            const QUX = FooAlias::class;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Bar\Bat');
        self::assertSame('My\Awesome\Foo', $classInfo->getConstantValue('QUX'));
    }

    public function testClassConstantResolutionFromParent(): void
    {
        $phpCode = '<?php
        namespace Bar;

        class Foo {
            const BAR = "baz";
        }
        class Bat extends Foo {
            private $property = self::BAR;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Bar\Bat');
        self::assertSame('baz', $classInfo->getProperty('property')->getDefaultValue());
    }

    public function testClassConstantResolutionFromParentParent(): void
    {
        $phpCode = '<?php
        namespace Bar;

        class Foo {
            const BAR = "baz";
        }
        class Bar extends Foo {}
        class Bat extends Bar {
            private $property = self::BAR;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Bar\Bat');
        self::assertSame('baz', $classInfo->getProperty('property')->getDefaultValue());
    }

    public function testDifferentClassConstantAsDefaultValueWhenInNamespace(): void
    {
        $phpCode = '<?php
        namespace Foo;

        class Foo {
            const BAR = "baz";
        }

        class Bar {
            private $property = Foo::BAR;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Foo\Bar');
        self::assertSame('baz', $classInfo->getProperty('property')->getDefaultValue());
    }

    public function testDifferentClassConstantAsDefaultValueWhenNotInNamespace(): void
    {
        $phpCode = '<?php
        class Foo {
            const BAR = "baz";
        }

        class Bar {
            private $property = Foo::BAR;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Bar');
        self::assertSame('baz', $classInfo->getProperty('property')->getDefaultValue());
    }

    public function testSelfStaticOrParentAsPropertyDefaultValue(): void
    {
        $phpCode = <<<'PHP'
        <?php
        
        class Baz {
            const PARENT_CONSTANT = 'parentConstant';
        }
        
        class Foo extends Baz {
            const SELF_CONSTANT = 'selfConstant';
            const STATIC_CONSTANT = 'staticConstant';
            const PARENT_CONSTANT = 'selfConstant';

            public $selfClass = self::class;
            public $staticClass = static::class;
            public $parentClass = parent::class;
            
            public $selfConstant = self::SELF_CONSTANT;
            public $staticConstant = self::STATIC_CONSTANT;
            public $parentConstant = parent::PARENT_CONSTANT;
        }
PHP;

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Foo');

        self::assertSame('Foo', $classInfo->getProperty('selfClass')->getDefaultValue());
        self::assertSame('Foo', $classInfo->getProperty('staticClass')->getDefaultValue());
        self::assertSame('Baz', $classInfo->getProperty('parentClass')->getDefaultValue());

        self::assertSame('selfConstant', $classInfo->getProperty('selfConstant')->getDefaultValue());
        self::assertSame('staticConstant', $classInfo->getProperty('staticConstant')->getDefaultValue());
        self::assertSame('parentConstant', $classInfo->getProperty('parentConstant')->getDefaultValue());
    }

    public function magicConstantsWithoutNamespaceProvider(): array
    {
        $dir = FileHelper::normalizeWindowsPath(realpath(__DIR__ . '/../Fixture'));

        return [
            ['_DIR', $dir],
            ['_FILE', $dir . '/MagicConstants.php'],
            ['_LINE', 7],
            ['_NAMESPACE', ''],
            ['_CLASS', ''],
            ['_TRAIT', ''],
            ['_METHOD', ''],
            ['_FUNCTION', ''],
        ];
    }

    /**
     * @dataProvider magicConstantsWithoutNamespaceProvider
     */
    public function testMagicConstantsWithoutNamespace(string $constantName, mixed $expectedValue): void
    {
        $reflector = new ConstantReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/MagicConstants.php', $this->astLocator), $this->classReflector);
        $constant  = $reflector->reflect($constantName);

        self::assertSame($expectedValue, $constant->getValue());
    }

    public function magicConstantsInNamespaceProvider(): array
    {
        $dir = FileHelper::normalizeWindowsPath(realpath(__DIR__ . '/../Fixture'));

        return [
            ['_DIR', $dir],
            ['_FILE', $dir . '/MagicConstants.php'],
            ['_LINE', 20],
            ['_NAMESPACE', 'Roave\BetterReflectionTest\Fixture'],
            ['_CLASS', ''],
            ['_TRAIT', ''],
            ['_METHOD', ''],
            ['_FUNCTION', ''],
        ];
    }

    /**
     * @dataProvider magicConstantsInNamespaceProvider
     */
    public function testMagicConstantsInNamespace(string $constantName, mixed $expectedValue): void
    {
        $reflector = new ConstantReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/MagicConstants.php', $this->astLocator), $this->classReflector);
        $constant  = $reflector->reflect('Roave\BetterReflectionTest\Fixture\\' . $constantName);

        self::assertSame($expectedValue, $constant->getValue());
    }

    public function magicConstantsInTraitProvider(): array
    {
        $dir = FileHelper::normalizeWindowsPath(realpath(__DIR__ . '/../Fixture'));

        return [
            ['dir', $dir],
            ['file', $dir . '/MagicConstants.php'],
            ['line', 31],
            ['namespace', 'Roave\BetterReflectionTest\Fixture'],
            ['class', 'Roave\BetterReflectionTest\Fixture\MagicConstantsTrait'],
            ['trait', 'Roave\BetterReflectionTest\Fixture\MagicConstantsTrait'],
            ['method', ''],
            ['function', ''],
        ];
    }

    /**
     * @dataProvider magicConstantsInTraitProvider
     */
    public function testMagicConstantsInTrait(string $propertyName, mixed $expectedValue): void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/MagicConstants.php', $this->astLocator));
        $class     = $reflector->reflect(MagicConstantsTrait::class);
        $property  = $class->getProperty($propertyName);

        self::assertSame($expectedValue, $property->getDefaultValue());
    }

    public function magicConstantsInClassProvider(): array
    {
        $dir = FileHelper::normalizeWindowsPath(realpath(__DIR__ . '/../Fixture'));

        return [
            ['dir', $dir],
            ['file', $dir . '/MagicConstants.php'],
            ['line', 43],
            ['namespace', 'Roave\BetterReflectionTest\Fixture'],
            ['class', 'Roave\BetterReflectionTest\Fixture\MagicConstantsClass'],
            ['trait', ''],
            ['method', ''],
            ['function', ''],
        ];
    }

    /**
     * @dataProvider magicConstantsInClassProvider
     */
    public function testMagicConstantsInClass(string $propertyName, mixed $expectedValue): void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/MagicConstants.php', $this->astLocator));
        $class     = $reflector->reflect(MagicConstantsClass::class);
        $property  = $class->getProperty($propertyName);

        self::assertSame($expectedValue, $property->getDefaultValue());
    }

    public function magicConstantsInMethodProvider(): array
    {
        $dir = FileHelper::normalizeWindowsPath(realpath(__DIR__ . '/../Fixture'));

        return [
            ['dir', $dir],
            ['file', $dir . '/MagicConstants.php'],
            ['line', 53],
            ['namespace', 'Roave\BetterReflectionTest\Fixture'],
            ['class', 'Roave\BetterReflectionTest\Fixture\MagicConstantsClass'],
            ['trait', ''],
            ['method', 'Roave\BetterReflectionTest\Fixture\MagicConstantsClass::magicConstantsMethod'],
            ['function', 'magicConstantsMethod'],
        ];
    }

    /**
     * @dataProvider magicConstantsInMethodProvider
     */
    public function testMagicConstantsInMethod(string $parameterName, mixed $expectedValue): void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/MagicConstants.php', $this->astLocator));
        $class     = $reflector->reflect(MagicConstantsClass::class);
        $method    = $class->getMethod('magicConstantsMethod');
        $parameter = $method->getParameter($parameterName);

        self::assertSame($expectedValue, $parameter->getDefaultValue());
    }

    public function magicConstantsInFunctionProvider(): array
    {
        $dir = FileHelper::normalizeWindowsPath(realpath(__DIR__ . '/../Fixture'));

        return [
            ['dir', $dir],
            ['file', $dir . '/MagicConstants.php'],
            ['line', 67],
            ['namespace', 'Roave\BetterReflectionTest\Fixture'],
            ['class', ''],
            ['trait', ''],
            ['method', 'Roave\BetterReflectionTest\Fixture\magicConstantsFunction'],
            ['function', 'Roave\BetterReflectionTest\Fixture\magicConstantsFunction'],
        ];
    }

    /**
     * @dataProvider magicConstantsInFunctionProvider
     */
    public function testMagicConstantsInFunction(string $parameterName, mixed $expectedValue): void
    {
        $reflector = new FunctionReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/MagicConstants.php', $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('Roave\BetterReflectionTest\Fixture\magicConstantsFunction');
        $parameter = $function->getParameter($parameterName);

        self::assertSame($expectedValue, $parameter->getDefaultValue());
    }
}
