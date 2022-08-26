<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Autoload\ClassLoaderMethod;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\Exception\SignatureCheckFailed;
use Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\FileCacheLoader;
use Roave\BetterReflection\Util\Autoload\ClassPrinter\ClassPrinterInterface;
use Roave\Signature\CheckerInterface;
use Roave\Signature\SignerInterface;

use function is_file;
use function sha1;
use function uniqid;
use function unlink;

/** @covers \Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\FileCacheLoader */
final class FileCacheLoaderTest extends TestCase
{
    public function testFileCacheWriterCreatesFileWithPrintedCode(): void
    {
        $className         = uniqid(__METHOD__, true);
        $generatedFilename = __DIR__ . '/' . sha1($className);

        $classInfo = $this->createMock(ReflectionClass::class);
        $classInfo->expects(self::exactly(2))->method('getName')->willReturn($className);

        $generatedCode = '// ' . uniqid(__METHOD__, true);
        $signature     = uniqid('Roave/Signature: ', true);
        $signedCode    = "<?php\n// " . $signature . "\n" . $generatedCode;

        $printer = $this->createMock(ClassPrinterInterface::class);
        $printer->expects(self::once())->method('__invoke')->with($classInfo)->willReturn($generatedCode);

        $signer = $this->createMock(SignerInterface::class);
        $signer->expects(self::once())->method('sign')->with("<?php\n" . $generatedCode)->willReturn($signature);

        $checker = $this->createMock(CheckerInterface::class);
        $checker->expects(self::exactly(2))->method('check')->with($signedCode)->willReturn(true);

        (new FileCacheLoader(__DIR__, $printer, $signer, $checker))->__invoke($classInfo);

        self::assertStringEqualsFile($generatedFilename, $signedCode);

        (new FileCacheLoader(__DIR__, $printer, $signer, $checker))->__invoke($classInfo);

        unlink($generatedFilename);
    }

    public function testExceptionThrownWhenSignatureFailedToVerify(): void
    {
        $className         = uniqid(__METHOD__, true);
        $generatedFilename = __DIR__ . '/' . sha1($className);

        $classInfo = $this->createMock(ReflectionClass::class);
        $classInfo->expects(self::exactly(2))->method('getName')->willReturn($className);

        $generatedCode = '// ' . uniqid(__METHOD__, true);
        $signature     = uniqid('Roave/Signature: ', true);
        $signedCode    = "<?php\n// " . $signature . "\n" . $generatedCode;

        $printer = $this->createMock(ClassPrinterInterface::class);
        $printer->expects(self::once())->method('__invoke')->with($classInfo)->willReturn($generatedCode);

        $signer = $this->createMock(SignerInterface::class);
        $signer->expects(self::once())->method('sign')->with("<?php\n" . $generatedCode)->willReturn($signature);

        $checker = $this->createMock(CheckerInterface::class);
        $checker->expects(self::once())->method('check')->with($signedCode)->willReturn(false);

        try {
            (new FileCacheLoader(__DIR__, $printer, $signer, $checker))->__invoke($classInfo);
            self::fail('Expected exception did not occur: ' . SignatureCheckFailed::class);
        } catch (SignatureCheckFailed) {
            return;
        } finally {
            if (is_file($generatedFilename)) {
                unlink($generatedFilename);
            }
        }
    }

    public function testDefaultFileCacheLoader(): void
    {
        $default = FileCacheLoader::defaultFileCacheLoader(__DIR__);
        self::assertInstanceOf(FileCacheLoader::class, $default);
    }
}
