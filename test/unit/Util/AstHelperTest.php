<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util;

use PhpParser\Node\Stmt;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\Util\AstHelper;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use Roave\BetterReflection\Reflection\ReflectionFunction;



final class AstHelperTest extends TestCase
{

    private Locator $astLocator;

    private $astHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $configuration    = BetterReflectionSingleton::instance();
        $this->astLocator = $configuration->astLocator();
        $this->astHelper = new AstHelper();
    }

    
    public function testForReflectionClass(): void
    {
        $php = '<?php
            class Foo { 
                private string $test;
            }
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectClass('Foo');
        
        $ast = $this->astHelper->forClass($function);

        self::assertInstanceOf(Class_::class, $ast[0]);
        self::assertSame('Foo', $ast[0]->name->name);
    }

    public function testForReflectionFunction(): void
    {
        $php = '<?php
            function foo() {
                return false;
            }
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');
        
        $ast = $this->astHelper->forFunction($function);

        self::assertInstanceOf(Function_::class, $ast[0]);
        self::assertSame('foo', $ast[0]->name->name);
    }

    public function testForReflectionFunctionClosure(): void
    {
        
        $foo = function (int $in) {
            return $in + 1;
        };
        
        $reflection = ReflectionFunction::createFromClosure($foo);
        
        $test = $this->astHelper->forClosure($reflection);
        
        $test = $this->astHelper->getBodyCode($test);
        self::assertSame('in', $reflection->getParameters()[0]->getName());
        self::assertSame('return $in + 1;', $test);

    }

    public function testForReflectionArrowFunction(): void
    {
        
        $foo = fn (int $in) => $in + 1;

        $reflection = ReflectionFunction::createFromClosure($foo);
       
        $test = $this->astHelper->forClosure($reflection);
        $test = $this->astHelper->getBodyCode($test);

        self::assertSame('in', $reflection->getParameters()[0]->getName());
        self::assertSame('$in + 1', $test);

    }

}