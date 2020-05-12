<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator;

use App\A;
use App\B;
use PhpParser\Lexer\Emulative;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Throwable;
use function spl_autoload_register;
use function var_dump;

class AutoloadSourceLocatorPharTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testPharWorks() : void
    {
        require_once 'phar://' . __DIR__ . '/test.phar/vendor/autoload.php';
        spl_autoload_register(static function (string $class) : void {
            if ($class !== B::class) {
                return;
            }

            include_once __DIR__ . '/B.php';
        });
        $a = new A();
        $this->assertInstanceOf(A::class, $a);
    }

    /**
     * @runInSeparateProcess
     */
    public function testPharDoesNotWork() : void
    {
        require_once 'phar://' . __DIR__ . '/test.phar/vendor/autoload.php';
        spl_autoload_register(static function (string $class) : void {
            if ($class !== B::class) {
                return;
            }

            include_once __DIR__ . '/B.php';
        });

        $parser            = new MemoizingParser((new ParserFactory())->create(ParserFactory::PREFER_PHP7, new Emulative([
            'usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'],
        ])));
        $functionReflector = null;
        $astLocator        = new AstLocator($parser, static function () use (&$functionReflector) : FunctionReflector {
            return $functionReflector;
        });
        $sourceLocator     = new AutoloadSourceLocator(
            $astLocator,
            $parser
        );
        $classReflector    = new ClassReflector($sourceLocator);
        $functionReflector = new FunctionReflector($sourceLocator, $classReflector);

        try {
            $reflection = $classReflector->reflect(A::class);
            $this->assertSame(A::class, $reflection->getName());
        } catch (Throwable $e) {
            var_dump($e->getMessage());
            die;
        }
    }
}
